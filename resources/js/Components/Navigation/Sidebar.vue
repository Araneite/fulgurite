<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import SidebarItem from "@/Components/Navigation/SidebarItem.vue";
import CsrfForm from "@/Components/Forms/CsrfForm.vue";

const page = usePage();

const user = computed(()=> page.props.auth?.user)
const userFirstChar = page.props.auth?.user.username.charAt(0).toUpperCase();

const trans = page.props.trans;

const sections = computed(() => page.props.dashboard?.pages ?? []);
</script>

<template>
    <aside class="min-h-screen w-56 shrink-0 bg-night py-4 text-white flex flex-col justify-between fixed border-e border-r-white/10">
        <div class="nav-start">
            <div class="px-3 pb-3 border-b border-b-white/10">
                <Link
                    href="/"
                    class="flex h-12 items-center gap-3 rounded-md px-2 hover:bg-white/5"
                >
                    <img :src="'/assets/img/fulgurite-logo.svg'" alt="Fulgurite" class="size-8">
                    <span class="font-semibold">Fulgurite</span>
                </Link>
            </div>
        
            <nav class="px-3 py-6">
                <section
                    v-for="section in sections"
                    :key="section.label"
                    class="space-y-2"
                >
                    <p class="px-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        {{ section.label }}
                    </p>
    
                    <div class="space-y-1">
                        <SidebarItem
                            v-for="item in section.items"
                            :key="item.key ?? item.url ?? item.label"
                            :item="item"
                        />
                    </div>
                </section>
            </nav>
        </div>
        
        <div class="nav-end flex flex-col px-3 py-5 border-t border-t-white/30 gap-2">
            <div class="user flex items-center justify-start gap-4 w-full">
                <div class="avatar rounded-full bg-primary text-midnight font-semibold size-8 flex items-center justify-center">
                    <p class="text-sm">{{ userFirstChar }}</p>
                </div>
                <div class="user-auth flex flex-col justify-center items-start text-white/80 gap-0.5">
                    <p class="username text-xs ">{{ user.username }}</p>
                    <p class="displayName text-sm text-white">{{ user.username.charAt(0).toUpperCase() + user.username.slice(1) }}</p>
                </div>
            </div>
            <Link href="/profile" class="btn btn-tertiary text-xs font-normal text-center py-2 px-3">
                {{ trans.layout.profile_btn }}
            </Link>
            <CsrfForm action="/logout" method="post">
                <button type="submit" class="link link-danger text-xs font-normal text-center w-full">
                    {{ trans.layout.logout }}
                </button>
            </CsrfForm>
        </div>
    </aside>
</template>
