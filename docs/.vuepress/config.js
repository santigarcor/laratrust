const { getDocsNavBar, getVersionsLinks, getActiveVersion } = require('./utils');

module.exports = {
  title: 'Laratrust',
  ga: 'UA-84408499-3',
  description: 'Laratrust is an easy and flexible way to add roles, permissions and teams authorization to your Laravel application',
  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
    ['meta', { name: 'robots', content: 'index, follow' }],
    ['meta', { property: 'og:image', content: 'https://laratrust.santigarcor.me/logo.png'}],
    ['meta', { property: 'og:description', content: 'Laratrust is an easy and flexible way to add roles, permissions and teams authorization to your Laravel application'}],
    ['meta', { property: 'twitter:description', content: 'Laratrust is an easy and flexible way to add roles, permissions and teams authorization to your Laravel application'}]
  ],
  themeConfig: {
    logo: '/logo.svg',
    activeVersion: getActiveVersion(),
    algolia: {
      apiKey: 'feb79d4f7397b8f410909711f924d524',
      indexName: 'laratrust',
      algoliaOptions: { facetFilters: [ "version:$VERSION$"] },
    },
    lastUpdated: 'Last Updated',
    // Assumes GitHub. Can also be a full GitLab url.
    repo: 'santigarcor/laratrust',
    // Customising the header label
    // Defaults to "GitHub"/"GitLab"/"Bitbucket" depending on `themeConfig.repo`
    repoLabel: 'Contribute!',

    // Optional options for generating "Edit this page" link

    // if your docs are in a different repo from your main project:
    docsRepo: 'santigarcor/laratrust',
    // if your docs are not at the root of the repo:
    docsDir: 'docs',
    // if your docs are in a specific branch (defaults to 'master'):
    docsBranch: 'master',
    // defaults to false, set to true to enable
    editLinks: true,
    // custom text for edit link. Defaults to "Edit this page"
    editLinkText: 'Help us improve this page!',
    sidebar: {
      '/docs/5.0/': getDocsNavBar('5.0'),
      '/docs/5.1/': getDocsNavBar('5.1'),
      '/docs/5.2/': getDocsNavBar('5.2'),
      '/docs/6.x/': getDocsNavBar('6.x'),
      '/docs/7.x/': getDocsNavBar('7.x'),
    },
    nav: [
      { text: 'Docs', link: getActiveVersion().link },
      { text: 'Version', items: getVersionsLinks() },
    ]
  }
}