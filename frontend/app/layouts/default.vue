<script setup lang="ts">
const { message, hide } = useToast();
</script>

<template>
  <div class="shell">
    <AppSidebar />

    <main class="content">
      <slot />
    </main>

    <div v-if="message" class="toast" @click="hide">
      {{ message }}
    </div>
  </div>
</template>
