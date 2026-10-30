---
sessionId: session-261030-145636-19br
---

# Requisitos

### Objetivo
Na visualização resumida dos projetos, permitir que as bancas de mestrado e doutorado vejam os documentos com rótulo `Memorial`, além dos documentos `Projeto`.

- Manter a visualização, o download e a abertura em nova janela existentes para ambos os tipos.
- Continuar ocultando os demais documentos na tela resumida e preservar a visualização atual dos avaliadores e gestores.
- Não exigir que exista Memorial; a ausência de qualquer um dos tipos não impede a exibição do outro.

# Desenho técnico

### Implementação
- `resources/js/pages/projects/Show.vue`: passar a `ProjectDataResume.vue` os papéis já disponíveis em `auth.roles`, sem alterar a escolha de `ProjectData.vue` para quem possui `projects.manage`.
- `resources/js/pages/projects/partials/ProjectDataResume.vue`: filtrar `project.content.documents` por rótulo, ignorando maiúsculas/minúsculas e espaços nas extremidades. Exibir `projeto` para todos os usuários da visão resumida e também `memorial` quando os papéis incluírem `master_committee` ou `doctorate_committee`; preservar a ordem e todos os documentos correspondentes.
- Usar a lista filtrada para controlar o título e a seção, evitando uma seção vazia quando não houver documentos permitidos. Reutilizar o diálogo e o tratamento de URLs atuais.

### Limite de segurança existente
`ProjectResource` envia o conteúdo completo e os arquivos usam URLs públicas: o filtro é apenas de apresentação. Proteção dos dados no servidor e alteração do armazenamento ou das permissões estão fora deste escopo.

# Testes

### Verificação automatizada
- Adicionar `tests/frontend/project-data-resume.test.mjs` com `node:test`, Vite, o plugin Vue e renderização SSR do componente real, usando as dependências já instaladas; executar com `node --test tests/frontend/project-data-resume.test.mjs`.
- Cobrir as duas bancas, avaliador sem papel de banca, múltiplos papéis, gestor pela seleção existente, rótulos com variações de caixa e espaços, outros documentos, listas vazias ou ausentes, apenas Memorial e múltiplas ocorrências. Verificar a presença dos cartões e seus botões de visualização.
- Ampliar `tests/Feature/Projects/ProjectDetailsTest.php` com cenários autorizados para cada banca e avaliador, conferindo `auth.roles` e os documentos enviados em `project.content.documents`; esses testes não substituem a verificação de renderização Vue.
- Executar o teste Pest específico e `npm run types:check`. Os testes SSR não simulam cliques no diálogo; o comportamento existente de visualização e download será reutilizado sem alteração.

# Delivery Steps

### ✓ Step 1: Liberar o Memorial na visão resumida das bancas
As bancas passam a receber cartões de Projeto e Memorial na visualização resumida.

- Em `Show.vue`, fornecer os papéis compartilhados ao componente resumido.
- Em `ProjectDataResume.vue`, aplicar o filtro de rótulos e papéis, preservando o diálogo e o fluxo de arquivos existentes.
- Adicionar testes de renderização Vue para as duas bancas, avaliador, múltiplos papéis e documentos não permitidos.
- Ampliar `ProjectDetailsTest.php` para conferir os papéis e documentos entregues pela página Inertia.

### ✓ Step 2: Tratar listas parciais e validar regressões
A seção apresenta apenas documentos disponíveis e mantém o comportamento dos demais perfis.

- Basear o título e a visibilidade da seção na lista filtrada, sem exigir ambos os tipos de documento.
- Cobrir variações de rótulo, ausência de documentos, apenas Memorial, ocorrências múltiplas e seleção da visão completa para gestores.
- Executar os testes frontend e Pest específicos e a verificação de tipos TypeScript.