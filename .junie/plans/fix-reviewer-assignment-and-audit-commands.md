---
sessionId: session-260917-161125-1xkd
---

# Requirements

### Overview & Goals
Garantir a máxima eficiência, integridade dos dados e confiabilidade no processo de distribuição de projetos para avaliadores, eliminando inconformidades que geram excesso de avaliadores por projeto e fornecendo ferramentas de auditoria e remediação imediata para produção.

### Scope
- **In Scope:**
  - Diagnóstico e correção da ação de troca/substituição de avaliador na etapa de distribuição (`DISTRIBUTION`).
  - Ajuste no componente Vue frontend (`ProjectList.vue`) para propagar o avaliador a ser substituído.
  - Ajuste no controller Laravel (`ReviewAssignmentController.php`) para tratar a troca atômica do avaliador.
  - Criação do comando Artisan para listar projetos que possuam mais de 3 avaliadores (`projects:excess-evaluators`).
  - Criação do comando Artisan para remover a atribuição de um avaliador específico de um projeto (`projects:remove-evaluator`).
  - Testes automatizados em Pest cobrindo a substituição e os comandos.
- **Out of Scope:**
  - Alterações nas regras de negócio das etapas posteriores (exame escrito, comitê ou homologação).
  - Alterações na estrutura do banco de dados (schema/migrations).

### User Stories
- **Como Administrador do Processo Seletivo**, quero substituir um avaliador já atribuído a um projeto na etapa de distribuição de forma que o novo avaliador substitua o anterior (mantendo o limite correto de avaliadores e a respectiva marcação de indicação).
- **Como Administrador/Suporte em Produção**, quero executar um comando de diagnóstico para identificar todos os projetos afetados que ficaram com mais de 3 avaliadores.
- **Como Administrador/Suporte em Produção**, quero executar um comando de correção que remova com segurança um avaliador excedente de um projeto informando os IDs correspondentes.

# Technical Design

### Current Implementation & Root Cause Analysis
1. **Comportamento Atual no Frontend (`resources/js/components/selection/ProjectList.vue`):**
   - Ao clicar no badge de um avaliador existente, a função `openSelectionModal(project, assignment, indicated)` armazena `selectedAssignment.value = assignment`.
   - Ao selecionar um novo avaliador no dropdown e clicar em *Salvar Atribuição* (`assignReviewer()`), o payload enviado via POST para `selectionRoutes.assignments.store` contém apenas `project_id`, `user_id` (o novo avaliador) e `chosen_by_candidate`.
   - A informação do avaliador anterior (`assignment.user_id`) é descartada.

2. **Comportamento Atual no Backend (`app/Http/Controllers/SelectionProcess/ReviewAssignmentController.php`):**
   - O método `store()` chama `ReviewService::createReviewAssignment()`, que executa:
     `$project->reviewAssignments()->updateOrCreate(['user_id' => $reviewer->id], ['chosen_by_candidate' => $chosen_by_candidate]);`
   - O registro do avaliador antigo nunca é excluído ou atualizado; apenas um novo registro para o novo avaliador é inserido na tabela `review_assignments`.
   - Como resultado, o projeto passa a ter 4 ou mais avaliadores. Na interface, caso seja um slot indicado, `getIndicatedAssignment()` faz `.find()` e continua exibindo o primeiro registro (o antigo), aparentando que a troca não ocorreu.

### Key Decisions
1. **Envio do `old_user_id` na requisição de atribuição:**
   - *Decisão:* Adicionar o campo opcional `old_user_id` no payload do endpoint `selection.assignments.store`.
   - *Justificativa:* Mantém o endpoint existente limpo e retrocompatível, permitindo que a mesma rota sirva tanto para adição em slot vazio quanto para substituição pontual em slot preenchido sem introduzir novas rotas desnecessárias.

2. **Substituição Transacional e Prevenção de Duplicidade:**
   - *Decisão:* No controller/serviço, executar a remoção do avaliador anterior e criação/atualização do novo avaliador dentro de `DB::transaction`. Validar se o novo avaliador já está alocado em outro slot do mesmo projeto para evitar alocações duplicadas.

3. **Comandos Artisan com Assinatura Expressiva:**
   - *Decisão:*
     - Diagnóstico: `projects:excess-evaluators {--selection= : Filtrar por processo seletivo}`
     - Remediação: `projects:remove-evaluator {project : ID do projeto} {user : ID do avaliador} {--force : Ignorar confirmação}`
   - *Justificativa:* Segue as convenções do Laravel e do projeto (`app/Console/Commands/`), com suporte tanto para execução interativa quanto automatizada/scriptada em produção.

### Proposed Changes

#### 1. Frontend: `resources/js/components/selection/ProjectList.vue`
- Em `assignReviewer()`, incluir `old_user_id: selectedAssignment.value?.user_id ?? null` no payload da requisição.

#### 2. Backend: `app/Http/Controllers/SelectionProcess/ReviewAssignmentController.php`
- Adicionar `'old_user_id' => ['nullable', 'exists:users,id']` na validação.
- Se `old_user_id` estiver presente e for diferente de `user_id`:
  - Verificar se o `user_id` já não está atribuído ao projeto em outro slot.
  - Remover a atribuição do `old_user_id` e criar/atualizar a do novo `user_id` dentro de uma transação `DB::transaction`.

#### 3. Comando de Diagnóstico: `app/Console/Commands/ProjectFindExcessEvaluatorsCommand.php`
- Consulta projetos com mais de 3 atribuições:
  ```php
  $query = Project::query()
      ->with(['reviewAssignments.user', 'selectionProcess'])
      ->withCount('reviewAssignments')
      ->having('review_assignments_count', '>', 3);
  ```
- Exibir tabela no console com colunas: `[ID Projeto, Candidato, Processo Seletivo, Total Avaliadores, Avaliadores (ID - Nome - Indicação)]`.

#### 4. Comando de Remediação: `app/Console/Commands/ProjectRemoveEvaluatorCommand.php`
- Localiza o projeto por ID e a respectiva `ReviewAssignment` pelo ID do usuário (`user_id`).
- Se não for passado `--force`, solicita confirmação exibindo detalhes do projeto e do avaliador.
- Remove a atribuição, verifica se o projeto precisa reajustar seu estágio via `ProjectService` caso a contagem caia abaixo de 3, e emite mensagem de sucesso.

### File Structure
- Modificados:
  - `resources/js/components/selection/ProjectList.vue`
  - `app/Http/Controllers/SelectionProcess/ReviewAssignmentController.php`
  - `tests/Feature/SelectionProcess/ReviewAssignmentTest.php`
- Adicionados:
  - `app/Console/Commands/ProjectFindExcessEvaluatorsCommand.php`
  - `app/Console/Commands/ProjectRemoveEvaluatorCommand.php`
  - `tests/Feature/Console/ProjectFindExcessEvaluatorsCommandTest.php`
  - `tests/Feature/Console/ProjectRemoveEvaluatorCommandTest.php`

# Testing

### Validation Approach
Validar as alterações por meio de testes automatizados unitários/feature com Pest, cobrindo fluxos de sucesso, validação e casos de borda.

### Key Scenarios
1. **Substituição de avaliador regular:**
   - Projeto com 3 avaliadores regulares (A, B, C).
   - Substituição de A por D via `selection.assignments.store` com `old_user_id = A`.
   - Verificar que a atribuição de A é removida, a de D é criada, e o projeto mantém exatamente 3 avaliadores (B, C, D).

2. **Substituição de avaliador indicado:**
   - Projeto com avaliador indicado A (`chosen_by_candidate = true`) e regulares B e C.
   - Substituição de A por D com `chosen_by_candidate = true` e `old_user_id = A`.
   - Verificar que A foi removido e D passa a ser o único avaliador com `chosen_by_candidate = true`.

3. **Tentativa de atribuir avaliador já alocado no mesmo projeto:**
   - Projeto com avaliadores A, B, C.
   - Tentar substituir A por B.
   - Verificar rejeição/erro informando duplicidade.

4. **Execução do comando `projects:excess-evaluators`:**
   - Criar projetos de teste: um com 3 avaliadores e outro com 4 avaliadores.
   - Executar o comando e verificar que apenas o projeto com 4 avaliadores é listado na saída.

5. **Execução do comando `projects:remove-evaluator`:**
   - Executar `projects:remove-evaluator <project_id> <user_id> --force`.
   - Verificar que o `ReviewAssignment` é excluído do banco de dados e a contagem passa para 3.
   - Testar tentativa de remoção de avaliador inexistente no projeto e verificar mensagem de erro apropriada.

# Delivery Steps

### ✓ Step 1: Correção da troca de avaliadores na distribuição
Corrigir o fluxo de substituição de avaliadores na tela de distribuição para garantir a remoção da atribuição anterior e integridade das alocações.

- Atualizar o componente `resources/js/components/selection/ProjectList.vue` para enviar o `old_user_id` (extraído de `selectedAssignment`) durante o submit do modal em `assignReviewer()`.
- Atualizar `app/Http/Controllers/SelectionProcess/ReviewAssignmentController.php` no método `store()` para validar o campo opcional `old_user_id`.
- Implementar a lógica transacional (`DB::transaction`) para remover a atribuição antiga quando `old_user_id` for informado e diferente de `user_id`.
- Adicionar validação para impedir que o mesmo avaliador seja duplicado no mesmo projeto.
- Criar/atualizar testes no Pest em `tests/Feature/SelectionProcess/ReviewAssignmentTest.php` cobrindo a substituição de avaliador indicado, substituição de avaliador comum e prevenção de duplicidade.

### ✓ Step 2: Comando de diagnóstico de projetos com excesso de avaliadores
Desenvolver comando Artisan para auditoria e listagem de projetos com mais de 3 avaliadores vinculados.

- Criar a classe de comando `app/Console/Commands/ProjectFindExcessEvaluatorsCommand.php` com assinatura `projects:excess-evaluators {--selection= : ID opcional do processo seletivo}`.
- Implementar consulta com `withCount('reviewAssignments')` e cláusula `having('review_assignments_count', '>', 3)`, carregando relacionamentos `reviewAssignments.user` e `selectionProcess`.
- Formatar a saída no console com tabela detalhada contendo ID do projeto, candidato/título, processo seletivo, contagem de avaliadores e lista detalhada de avaliadores (ID, Nome, Indicação).
- Adicionar testes automatizados no Pest em `tests/Feature/Console/ProjectFindExcessEvaluatorsCommandTest.php`.

### ✓ Step 3: Comando de remoção de avaliador para remediação em produção
Desenvolver comando Artisan para remoção pontual e segura de um avaliador de um projeto em ambiente de produção.

- Criar a classe de comando `app/Console/Commands/ProjectRemoveEvaluatorCommand.php` com assinatura `projects:remove-evaluator {project : ID do projeto} {user : ID do avaliador} {--force : Executar sem confirmação interativa}`.
- Implementar busca de `Project` e respectivo `ReviewAssignment` pelo `user_id`.
- Validar a existência da atribuição e do projeto, exibindo mensagens de erro claras caso não existam.
- Executar a exclusão da atribuição e atualizar o estágio do projeto via `ProjectService` caso a contagem caia abaixo de 3.
- Exibir confirmação interativa em modo interativo (a menos que `--force` esteja presente) e emitir feedback de sucesso com dados do projeto e do avaliador removido.
- Adicionar testes automatizados no Pest em `tests/Feature/Console/ProjectRemoveEvaluatorCommandTest.php`.