<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Folder,
    LayoutGrid,
    Menu,
    UserCogIcon,
    Layers2Icon,
    FileArchive,
    FileSpreadsheet,
    Download,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import SelectionProcessSwitcher from '@/components/SelectionProcessSwitcher.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    show as selectionProcessShow,
    evaluate as evaluateList,
    committee as committeeList,
    writtenExam as writtenExamList,
} from '@/routes/selection';
import selectionRoute from '@/routes/selection';
import routeProjects from '@/routes/selection/projects';
import { index as teamList } from '@/routes/team';
import type { BreadcrumbItem, NavItem } from '@/types';
import { authCan } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

const mainNavItems = computed((): NavItem[] => {
    const result: NavItem[] = [
        {
            title: 'Painel',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (
        auth.value.currentSelectionProcess &&
        (authCan(auth.value, 'users.manage') ||
        authCan(auth.value, 'committee.evaluate'))
    ) {
        result.push({
            title: 'Processo seletivo',
            href: selectionProcessShow(auth.value.currentSelectionProcess),
            icon: Layers2Icon,
        });
    }

    if (
        auth.value.currentSelectionProcess &&
        authCan(auth.value, 'projects.view')
    ) {
        const isAdmin =
            authCan(auth.value, 'projects.manage') ||
            auth.value.roles.includes('admin');
        const allowedPhases = [
            'DISTRIBUTION',
            'REVIEW',
            'WRITTEN_EXAM',
            'COMMITTEE',
            'RESULTS',
            'FINISHED',
        ];

        if (
            isAdmin ||
            allowedPhases.includes(auth.value.currentSelectionProcess.phase)
        ) {
            result.push({
                title: 'Projetos',
                href: routeProjects.index(auth.value.currentSelectionProcess),
                icon: FileArchive,
            });
        }
    }

    if (
        auth.value.roles.includes('master_committee') &&
        authCan(auth.value, 'committee.evaluate')
    ) {
        result.push({
            title: 'Provas escritas',
            href: writtenExamList(auth.value.currentSelectionProcess),
            icon: FileArchive,
        });
    }

    if (
        auth.value.currentSelectionProcess &&
        authCan(auth.value, 'review.evaluate')
    ) {
        result.push({
            title: 'Avaliar',
            href: evaluateList(auth.value.currentSelectionProcess),
            icon: FileArchive,
        });
    }

    if (
        auth.value.currentSelectionProcess &&
        authCan(auth.value, 'committee.evaluate')
    ) {
        result.push({
            title: 'Provas orais',
            href: committeeList(auth.value.currentSelectionProcess),
            icon: FileArchive,
        });
    }

    return result;
});

const isAdmin = computed(() => {
    return (
        auth.value.roles.includes('admin') ||
        authCan(auth.value, 'projects.manage')
    );
});

const reportItems = computed(() => {
    if (!auth.value.currentSelectionProcess || !isAdmin.value) {
        return [];
    }

    const sel = auth.value.currentSelectionProcess;

    return [
        {
            title: 'Homologação',
            href: selectionRoute.projects.homologation.report({ selection: sel.id }),
        },
        {
            title: 'Distribuição',
            href: selectionRoute.projects.distribution.report({ selection: sel.id }),
        },
        {
            title: 'Avaliação',
            href: selectionRoute.projects.review.report({ selection: sel.id }),
        },
        {
            title: 'Prova Escrita',
            href: selectionRoute.projects.writtenExam.report({ selection: sel.id }),
        },
        {
            title: 'Comitê',
            href: selectionRoute.projects.committee.report({ selection: sel.id }),
        },
        {
            title: 'Resultado Final',
            href: selectionRoute.projects.finalResult.report({ selection: sel.id }),
        },
        {
            title: 'Ações Afirmativas',
            href: selectionRoute.projects.affirmativeAction.report({ selection: sel.id }),
        },
    ];
});

const teamNavItem = computed((): NavItem | null => {
    if (!authCan(auth.value, 'users.manage')) {
        return null;
    }

    return {
        title: 'Equipe',
        href: teamList(),
        icon: UserCogIcon,
        target: '_self',
    };
});

const documentsNavItem = computed((): NavItem | null => {
    if (
        !auth.value.currentSelectionProcess ||
        !auth.value.roles.includes('admin')
    ) {
        return null;
    }

    return {
        title: 'Arquivos',
        href: selectionRoute.documents.index(),
        icon: Folder,
        target: '_self',
    };
});

const rightNavItems = computed((): NavItem[] => {
    const result: NavItem[] = [];

    if (teamNavItem.value) {
        result.push(teamNavItem.value);
    }

    if (documentsNavItem.value) {
        result.push(documentsNavItem.value);
    }

    return result;
});

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-75 p-6">
                            <SheetTitle class="sr-only">Menu</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogo
                                    class="h-9 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="
                                            isCurrentOrParentUrl(item.href)
                                                ? activeItemStyles
                                                : ''
                                        "
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        :target="item?.target ?? '_blank'"
                                        rel="noopener noreferrer"
                                        :class="[
                                            'flex items-center space-x-2 text-sm font-medium',
                                            {
                                                'bg-muted':
                                                    isCurrentOrParentUrl(
                                                        item.href,
                                                    ),
                                            },
                                        ]"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>

                                    <div
                                        v-if="reportItems.length > 0"
                                        class="border-t border-sidebar-border/70 pt-4"
                                    >
                                        <div
                                            class="px-1 pb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                                        >
                                            Relatórios
                                        </div>
                                        <div class="space-y-1">
                                            <a
                                                v-for="report in reportItems"
                                                :key="report.title"
                                                :href="toUrl(report.href)"
                                                download
                                                class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm font-medium hover:bg-accent"
                                            >
                                                <span
                                                    class="flex items-center gap-x-2"
                                                >
                                                    <FileSpreadsheet
                                                        class="h-4 w-4 opacity-70"
                                                    />
                                                    {{ report.title }}
                                                </span>
                                                <Download
                                                    class="h-4 w-4 opacity-70"
                                                />
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-2">
                    <AppLogo class="h-9" />
                </Link>

                <div class="ml-4 hidden lg:block">
                    <SelectionProcessSwitcher />
                </div>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href,
                                            activeItemStyles,
                                        ),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <div class="hidden items-center space-x-1 lg:flex">
                            <TooltipProvider
                                v-if="teamNavItem"
                                :delay-duration="0"
                            >
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            as-child
                                            class="group h-9 w-9 cursor-pointer"
                                        >
                                            <a
                                                :href="toUrl(teamNavItem.href)"
                                                :target="
                                                    teamNavItem?.target ??
                                                    '_blank'
                                                "
                                                rel="noopener noreferrer"
                                            >
                                                <span class="sr-only">{{
                                                    teamNavItem.title
                                                }}</span>
                                                <component
                                                    :is="teamNavItem.icon"
                                                    class="size-5 opacity-80 group-hover:opacity-100"
                                                />
                                            </a>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{{ teamNavItem.title }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>

                            <DropdownMenu v-if="reportItems.length > 0">
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="group h-9 w-9 cursor-pointer"
                                        aria-label="Relatórios"
                                        title="Relatórios"
                                    >
                                        <span class="sr-only">Relatórios</span>
                                        <FileSpreadsheet
                                            class="size-5 opacity-80 group-hover:opacity-100"
                                        />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-56">
                                    <DropdownMenuLabel
                                        >Relatórios para
                                        download</DropdownMenuLabel
                                    >
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        v-for="report in reportItems"
                                        :key="report.title"
                                        as-child
                                    >
                                        <a
                                            :href="toUrl(report.href)"
                                            download
                                            class="flex w-full cursor-pointer items-center justify-between"
                                        >
                                            <span>{{ report.title }}</span>
                                            <Download
                                                class="ml-2 h-4 w-4 opacity-70"
                                            />
                                        </a>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <TooltipProvider
                                v-if="documentsNavItem"
                                :delay-duration="0"
                            >
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            as-child
                                            class="group h-9 w-9 cursor-pointer"
                                        >
                                            <a
                                                :href="
                                                    toUrl(documentsNavItem.href)
                                                "
                                                :target="
                                                    documentsNavItem?.target ??
                                                    '_blank'
                                                "
                                                rel="noopener noreferrer"
                                            >
                                                <span class="sr-only">{{
                                                    documentsNavItem.title
                                                }}</span>
                                                <component
                                                    :is="documentsNavItem.icon"
                                                    class="size-5 opacity-80 group-hover:opacity-100"
                                                />
                                            </a>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{{ documentsNavItem.title }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
            </div>
        </div>
    </div>
</template>
