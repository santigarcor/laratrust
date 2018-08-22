<template>
  <router-link :to="finalTo">
    <slot></slot>
  </router-link>
</template>

<script>
import { getCurrentPageVersion } from '../theme/util';

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
    this.currentPageVersion = getCurrentPageVersion(this.$page.path, this.activeVersion.text);
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
  }
}
</script>