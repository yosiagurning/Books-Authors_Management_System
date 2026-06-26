import { createRouter, createWebHistory } from 'vue-router'

import AuthorsPage from '@/pages/AuthorsPage.vue'
import BooksPage from '@/pages/BooksPage.vue'
import DashboardPage from '@/pages/DashboardPage.vue'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: DashboardPage,
      meta: {
        title: 'Dashboard',
        description: 'Ringkasan cepat dan data terbaru untuk author dan book.',
      },
    },
    {
      path: '/authors',
      name: 'authors',
      component: AuthorsPage,
      meta: {
        title: 'Authors',
        description: 'Kelola author, detail, filter, dan relasi book dari satu halaman.',
      },
    },
    {
      path: '/books',
      name: 'books',
      component: BooksPage,
      meta: {
        title: 'Books',
        description: 'Kelola katalog book, filter berdasarkan author, dan cek detail item.',
      },
    },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})
