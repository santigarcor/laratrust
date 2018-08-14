<template>
  <router-link :to="finalTo">
    <slot></slot>
  </router-link>
</template>

<script>
  export default {
    props: ['to'],
    data() {
      return {
        activeVersion: '',
        currentPageVersion: '',
      }
    },
    created() {
      this.activeVersion = this.$site.themeConfig.activeVersion;
      this.currentPageVersion = this.getCurrentPageVersion();
    },
    computed: {
      finalTo() {
        const tempTo = this.to.startsWith('/')
          ? this.to.slice(1)
          : this.to;

        const versionLink = this.activeVersion.text == this.currentPageVersion
          ? this.activeVersion.link
          : `/docs/${this.currentPageVersion}/`;

        return `${versionLink}${tempTo}`;
      }
    },
    methods: {
      getCurrentPageVersion() {
        const matches = this.$page.path.match(/([0-9]*[.])?[0-9]+/);

        return matches == null || matches.length == 0
          ? this.activeVersion.text
          : matches[0];
      }
    },
  }
</script>