import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { after, test } from 'node:test';
import { resolve } from 'node:path';
import vue from '@vitejs/plugin-vue';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { createServer } from 'vite';

const vite = await createServer({
    configFile: false,
    root: process.cwd(),
    plugins: [vue()],
    resolve: {
        alias: {
            '@': resolve(process.cwd(), 'resources/js'),
        },
    },
    server: {
        middlewareMode: true,
    },
    appType: 'custom',
});

const { default: ProjectDataResume } = await vite.ssrLoadModule(
    '/resources/js/pages/projects/partials/ProjectDataResume.vue',
);

after(async () => {
    await vite.close();
});

const documents = [
    { label: 'Projeto', name: 'projeto.pdf', filename: 'projeto.pdf' },
    { label: 'Memorial', name: 'memorial.pdf', filename: 'memorial.pdf' },
    { label: 'Ficha', name: 'ficha.pdf', filename: 'ficha.pdf' },
];

async function renderProject(project, roles) {
    return renderToString(
        createSSRApp(ProjectDataResume, {
            project,
            roles,
        }),
    );
}

async function renderDocuments(roles, projectDocuments = documents) {
    return renderProject({ content: { documents: projectDocuments } }, roles);
}

function assertRenderedDocuments(markup, visibleNames, hiddenNames = []) {
    for (const name of visibleNames) {
        assert.ok(markup.includes(name), `expected ${name} to be rendered`);
    }

    for (const name of hiddenNames) {
        assert.ok(!markup.includes(name), `expected ${name} to be hidden`);
    }

    assert.equal(
        markup.match(/title="Visualizar arquivo"/g)?.length ?? 0,
        visibleNames.length,
    );
}

for (const role of ['master_committee', 'doctorate_committee']) {
    test(`${role} sees project and memorial documents`, async () => {
        const markup = await renderDocuments([role]);

        assertRenderedDocuments(markup, ['projeto.pdf', 'memorial.pdf'], [
            'ficha.pdf',
        ]);
    });
}

test('a reviewer sees project documents but not memorial or other documents', async () => {
    const markup = await renderDocuments(['reviewer']);

    assertRenderedDocuments(markup, ['projeto.pdf'], [
        'memorial.pdf',
        'ficha.pdf',
    ]);
});

test('a user with multiple roles sees memorial documents when any role is a committee', async () => {
    const markup = await renderDocuments(['reviewer', 'doctorate_committee']);

    assertRenderedDocuments(markup, ['projeto.pdf', 'memorial.pdf'], [
        'ficha.pdf',
    ]);
});

test('document labels ignore surrounding spaces and letter case while preserving all matches in order', async () => {
    const markup = await renderDocuments(['master_committee'], [
        { label: ' PROJETO ', name: 'primeiro.pdf', filename: 'primeiro.pdf' },
        { label: ' mEmOrIaL ', name: 'memorial.pdf', filename: 'memorial.pdf' },
        { label: 'projeto', name: 'segundo.pdf', filename: 'segundo.pdf' },
        { label: 'Ficha', name: 'ficha.pdf', filename: 'ficha.pdf' },
    ]);

    assertRenderedDocuments(
        markup,
        ['primeiro.pdf', 'memorial.pdf', 'segundo.pdf'],
        ['ficha.pdf'],
    );
    assert.ok(markup.indexOf('primeiro.pdf') < markup.indexOf('memorial.pdf'));
    assert.ok(markup.indexOf('memorial.pdf') < markup.indexOf('segundo.pdf'));
    assert.ok(markup.includes('Projeto e Memorial'));
});

test('a committee sees a memorial without requiring a project document', async () => {
    const markup = await renderDocuments(
        ['doctorate_committee'],
        [{ label: 'Memorial', name: 'memorial.pdf', filename: 'memorial.pdf' }],
    );

    assertRenderedDocuments(markup, ['memorial.pdf']);
    assert.ok(markup.includes('>Memorial</h4>'));
});

test('it does not render an empty document section when no visible documents exist', async () => {
    const projects = [
        { content: { documents: [] } },
        { content: {} },
        {},
        { content: { documents: [documents[2]] } },
    ];

    for (const project of projects) {
        const markup = await renderProject(project, ['reviewer']);

        assert.doesNotMatch(markup, /<h4/);
        assertRenderedDocuments(markup, []);
    }
});

test('a manager continues to select the full project data component', async () => {
    const showPage = await readFile(
        new URL('../../resources/js/pages/projects/Show.vue', import.meta.url),
        'utf8',
    );

    assert.match(showPage, /<ProjectData\s+v-if="authCan\(auth, 'projects\.manage'\)"/);
    assert.match(showPage, /<ProjectDataResume\s+v-else/);
});