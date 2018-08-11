<template>
  <router-link :to="finalTo">
    <slot></slot>
  </router-link>
</template>

<script>
  export default {
    props: ['to'],
    computed: {
      finalTo() {
        let tempTo = this.to.startsWith('/')
          ? this.to.slice(1)
          : this.to;

        return `${this.$site.themeConfig.activeVersion.link}${tempTo}`;
      }
    },
  }
</script>