const apiTarget = process.env.NUXT_API_BASE || "http://localhost:8000/api";

export default defineNuxtConfig({
  compatibilityDate: "2026-11-09",
  devtools: { enabled: false },
  css: ["~/assets/css/main.css"],
  app: {
    head: {
      htmlAttrs: { lang: "ru" },
      title: "График смен",
      meta: [
        { name: "viewport", content: "width=device-width, initial-scale=1" },
        { name: "description", content: "График смен сотрудников" },
      ],
      link: [
        { rel: "preconnect", href: "https://fonts.googleapis.com" },
        {
          rel: "preconnect",
          href: "https://fonts.gstatic.com",
          crossorigin: "",
        },
        {
          rel: "stylesheet",
          href: "https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap",
        },
      ],
    },
  },
  // Браузер и SSR ходят в Laravel через один origin — CORS не нужен.
  routeRules: {
    "/api/**": { proxy: `${apiTarget}/**` },
  },
  typescript: {
    strict: true,
  },
});
