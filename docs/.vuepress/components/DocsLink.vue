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
        const currentPageVersion = this.getCurrentPageVersion();
        const activeVersion = this.$site.themeConfig.activeVersion.link;
        const tempTo = this.to.startsWith('/')
          ? this.to.slice(1)
          : this.to;
        const versionLink = activeVersion == currentPageVersion
          ? this.$site.themeConfig.activeVersion.link
          : `/docs/${currentPageVersion}/`;

        return `${versionLink}${tempTo}`;
      }
    },
    methods: {
      getCurrentPageVersion() {
        return this.$page.path.match(/([0-9]*[.])?[0-9]+/)[0];
      }
    },
  }
</script>