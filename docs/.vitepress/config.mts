import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'oz.router',
  description: 'Роутер для Bitrix D7 с PHP-DI, guards, middleware и валидацией DTO',
  lang: 'ru-RU',
  lastUpdated: true,
  themeConfig: {
    nav: [
      { text: 'Главная', link: '/' },
      { text: 'Routing Guide', link: '/routing' },
      { text: 'Старт', link: '/getting-started' },
      { text: 'Архитектура', link: '/architecture' },
      { text: 'Конфигурация', link: '/configuration' }
    ],

    sidebar: [
      {
        text: 'Основы',
        items: [
          { text: 'Маршрутизация', link: '/routing' },
          { text: 'Старт и точки входа', link: '/getting-started' },
          { text: 'Архитектура и runtime', link: '/architecture' },
        ]
      },
      {
        text: 'Углубление',
        items: [
          { text: 'Guards', link: '/guards' },
          { text: 'Middleware', link: '/middleware' },
          { text: 'Валидация', link: '/validation' },
          { text: 'Конфигурация', link: '/configuration' }
        ]
      }
    ],

    socialLinks: [
      {
        icon: 'github',
        link: 'https://github.com/oziside/oz.router',
        ariaLabel: 'GitHub'
      }
    ],

    footer: {
      message: 'Распространяется под лицензией MIT'
    }
  }
})
