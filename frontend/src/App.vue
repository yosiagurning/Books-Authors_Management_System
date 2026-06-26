<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'

import { useAppRefresh } from '@/composables/useAppRefresh'

const route = useRoute()
const { notifyDataChanged } = useAppRefresh()

const sections = [
  {
    key: 'dashboard',
    to: '/',
    label: 'Dashboard',
    description: 'Ringkasan cepat dan data terbaru',
  },
  {
    key: 'authors',
    to: '/authors',
    label: 'Authors',
    description: 'Kelola author, detail, dan relasi book',
  },
  {
    key: 'books',
    to: '/books',
    label: 'Books',
    description: 'Kelola katalog book dan author terkait',
  },
] as const

const currentTitle = computed(() => String(route.meta.title ?? 'Dashboard'))
const currentDescription = computed(() =>
  String(
    route.meta.description ??
      'Antarmuka internal untuk mengelola master data author dan book.',
  ),
)
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand">
        <h1>Books & Authors</h1>
        <p>Management System berbasis Laravel API dan Vue.js.</p>
      </div>

      <nav class="nav-list">
        <RouterLink
          v-for="section in sections"
          :key="section.key"
          :to="section.to"
          class="nav-button"
          active-class="is-active"
        >
          <strong>{{ section.label }}</strong>
          <div class="muted">{{ section.description }}</div>
        </RouterLink>
      </nav>
    </aside>

    <main class="main-content">
      <header class="hero">
        <div>
          <h2>{{ currentTitle }}</h2>
          <p>{{ currentDescription }}</p>
        </div>

        <div class="actions">
          <button class="button button-ghost" type="button" @click="notifyDataChanged">
            Sinkronkan Semua Data
          </button>
        </div>
      </header>

      <RouterView />
    </main>
  </div>
</template>
