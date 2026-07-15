<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import imageBgAuth from '@/assets/images/bg-auth.jpg';
import AppLogo from '@/components/AppLogo.vue';
import { register } from '@/routes';
import { dashboard, home, login } from '@/routes';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            class="mb-6 w-full max-w-83.75 text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Painel
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Entrar
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Registrar
                    </Link>
                </template>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-83.75 flex-col-reverse overflow-hidden rounded-4xl lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="relative flex h-96 w-full flex-col items-center justify-center bg-muted text-white"
                >
                    <div
                        class="absolute inset-0 bg-transparent bg-cover bg-center opacity-100"
                        :style="{
                            backgroundImage: `url(${imageBgAuth})`,
                        }"
                    />
                    <Link
                        :href="home()"
                        class="relative z-20 flex items-center text-lg font-medium opacity-100 transition-opacity delay-300 duration-750 starting:opacity-0"
                    >
                        <AppLogo class-name="mr-2 h-20 fill-current" />
                    </Link>
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
