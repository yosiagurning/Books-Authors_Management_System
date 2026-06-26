<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import {
  LayoutDashboard,
  Users,
  BookOpen,
} from 'lucide-vue-next'

const route = useRoute()

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
    description: 'Kelola author dan relasi book',
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
</script>

<template>
  <div class="app-shell">
    <!-- =========================
         SIDEBAR
         ========================= -->
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-mark">
          <img src="/logo.png" alt="Books & Authors" />
        </div>
        <div>
          <h1>Books &amp; Authors</h1>
          <p>Management Console</p>
        </div>
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
          >
            <span class="nav-icon" aria-hidden="true">
              <component :is="section.icon" :size="18" :stroke-width="1.75" />
            </span>
            <span class="min-w-0 flex-1">
              <strong>{{ section.label }}</strong>
              <span class="nav-desc">{{ section.description }}</span>
            </span>
          </RouterLink>
        </nav>
      </div>
    </aside>

    <!-- =========================
         MAIN
         ========================= -->
    <main class="main-content">
      <header class="hero">
        <div class="min-w-0">
          <span class="hero-eyebrow">{{ currentEyebrow }}</span>
          <h2>{{ currentTitle }}</h2>
          <p>{{ currentDescription }}</p>
        </div>

      </header>

      <RouterView />
    </main>
  </div>
</template>
