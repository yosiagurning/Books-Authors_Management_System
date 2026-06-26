<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import {
  LayoutDashboard,
  Users,
  BookOpen,
  Menu,
  X,
  Sun,
  Moon,
  Sparkles,
} from 'lucide-vue-next'
import { useTheme } from '@/lib/theme'
import ToastHost from '@/components/ToastHost.vue'

const route = useRoute()
const { theme, setTheme } = useTheme()
const mobileOpen = ref(false)

const sections = [
  {
    key: 'dashboard',
    to: '/',
    label: 'Dashboard',
    description: 'Ringkasan & metrik utama',
    icon: LayoutDashboard,
  },
  {
    key: 'authors',
    to: '/authors',
    label: 'Authors',
    description: 'Kelola author & relasi book',
    icon: Users,
  },
  {
    key: 'books',
    to: '/books',
    label: 'Books',
    description: 'Katalog book & metadata',
    icon: BookOpen,
  },
] as const

const currentTitle = computed(() => String(route.meta.title ?? 'Dashboard'))
const currentDescription = computed(() =>
  String(
    route.meta.description ??
      'Antarmuka internal untuk mengelola master data author dan book.',
  ),
)
const currentEyebrow = computed(() => {
  const match = sections.find((s) => s.to === route.path)
  return match?.label ?? 'Overview'
})

function closeMobile() {
  mobileOpen.value = false
}
</script>

<template>
  <div class="app-shell">
    <!-- Mobile sidebar backdrop -->
    <div
      v-if="mobileOpen"
      class="sidebar-backdrop lg:hidden"
      data-testid="sidebar-backdrop"
      @click="closeMobile"
    />

    <!-- =========================
         SIDEBAR
         ========================= -->
    <aside :class="['sidebar', { 'is-open': mobileOpen }]" data-testid="sidebar">
      <div class="brand">
        <div class="brand-mark">
          <img src="/logo.png" alt="Books & Authors" />
        </div>
        <div class="min-w-0 flex-1">
          <h1>Books <em>&amp;</em> Authors</h1>
          <p>Management Console</p>
        </div>
        <button
          class="button button-ghost button-icon lg:hidden"
          type="button"
          aria-label="Tutup menu"
          data-testid="sidebar-close"
          @click="closeMobile"
        >
          <X :size="16" :stroke-width="2" />
        </button>
      </div>

      <div>
        <div class="nav-section-label">Workspace</div>
        <nav class="nav-list" aria-label="Primary">
          <RouterLink
            v-for="section in sections"
            :key="section.key"
            :to="section.to"
            class="nav-button"
            active-class="is-active"
            :exact-active-class="section.to === '/' ? 'is-active' : ''"
            :data-testid="`nav-${section.key}`"
            @click="closeMobile"
          >
            <span class="nav-icon" aria-hidden="true">
              <component :is="section.icon" :size="16" :stroke-width="2" />
            </span>
            <span class="min-w-0 flex-1">
              <strong>{{ section.label }}</strong>
              <span class="nav-desc">{{ section.description }}</span>
            </span>
          </RouterLink>
        </nav>
      </div>

      <!-- Footer with theme toggle -->
      <div class="sidebar-foot">
        <span class="sidebar-foot-label">Theme</span>
        <div class="theme-toggle" role="group" aria-label="Theme">
          <button
            type="button"
            :class="{ 'is-active': theme === 'light' }"
            aria-label="Light mode"
            data-testid="theme-light"
            @click="setTheme('light')"
          >
            <Sun :size="14" :stroke-width="2" />
          </button>
          <button
            type="button"
            :class="{ 'is-active': theme === 'dark' }"
            aria-label="Dark mode"
            data-testid="theme-dark"
            @click="setTheme('dark')"
          >
            <Moon :size="14" :stroke-width="2" />
          </button>
        </div>
      </div>
    </aside>

    <!-- =========================
         MAIN
         ========================= -->
    <main class="main-content">
      <!-- Mobile topbar -->
      <div class="mobile-bar">
        <button
          class="button button-ghost button-icon"
          type="button"
          aria-label="Buka menu"
          data-testid="sidebar-open"
          @click="mobileOpen = true"
        >
          <Menu :size="18" :stroke-width="2" />
        </button>
        <h1>Books &amp; Authors</h1>
        <div class="theme-toggle" role="group" aria-label="Theme">
          <button
            type="button"
            :class="{ 'is-active': theme === 'light' }"
            aria-label="Light mode"
            @click="setTheme('light')"
          >
            <Sun :size="14" :stroke-width="2" />
          </button>
          <button
            type="button"
            :class="{ 'is-active': theme === 'dark' }"
            aria-label="Dark mode"
            @click="setTheme('dark')"
          >
            <Moon :size="14" :stroke-width="2" />
          </button>
        </div>
      </div>

      <header class="hero">
        <div class="min-w-0">
          <span class="hero-eyebrow">{{ currentEyebrow }}</span>
          <h2>
            <template v-if="currentEyebrow === 'Dashboard'">
              Overview <em>singkat</em>
            </template>
            <template v-else-if="currentEyebrow === 'Authors'">
              Kelola <em>authors</em>
            </template>
            <template v-else-if="currentEyebrow === 'Books'">
              Katalog <em>buku</em>
            </template>
            <template v-else>{{ currentTitle }}</template>
          </h2>
          <p>{{ currentDescription }}</p>
        </div>

        <div class="hero-actions hidden lg:flex">
          <span class="badge badge-neutral">
            <Sparkles :size="12" :stroke-width="2" />
            Management Console v2
          </span>
        </div>
      </header>

      <RouterView v-slot="{ Component }">
        <transition name="page-fade" mode="out-in">
          <component :is="Component" :key="route.fullPath" />
        </transition>
      </RouterView>
    </main>

    <ToastHost />
  </div>
</template>
