import Alpine from 'alpinejs';

/**
 * A list of checkboxes with a filter, "All"/"None" buttons and a selected count.
 * Locked checkboxes (data-locked) are never changed.
 *
 * Uses $root, not $el: in a method called from a button, $el is that button.
 */
Alpine.data('laratrustChecklist', () => ({
    search: '',
    selected: 0,

    init() {
        this.count();
        this.$root.addEventListener('change', () => this.count());
    },

    matches(text) {
        return text.includes(this.search.trim().toLowerCase());
    },

    visibleBoxes() {
        return [...this.$root.querySelectorAll('input[type=checkbox]:not([data-locked])')]
            .filter((box) => box.closest('label').offsetParent !== null);
    },

    selectAll() {
        this.visibleBoxes().forEach((box) => (box.checked = true));
        this.count();
    },

    selectNone() {
        this.visibleBoxes().forEach((box) => (box.checked = false));
        this.count();
    },

    count() {
        this.selected = this.$root.querySelectorAll('input[type=checkbox]:checked').length;
    },
}));

/**
 * The role/permission form: on creation, the name (code) follows the display name, in kebab case.
 */
Alpine.data('laratrustNameFromDisplayName', (name, displayName, generate) => ({
    name,
    displayName,

    init() {
        if (generate) {
            this.$watch('displayName', (value) => (this.name = kebabCase(value)));
        }
    },
}));

/**
 * A confirmation dialog for a destructive form: open(action, label) sets the form action.
 */
Alpine.data('laratrustConfirm', () => ({
    action: '',
    label: '',

    open(action, label) {
        this.action = action;
        this.label = label;
        this.$refs.dialog.showModal();
    },

    close() {
        this.$refs.dialog.close();
    },
}));

function kebabCase(value) {
    return (value.match(/[A-Z]{2,}(?=[A-Z][a-z]+[0-9]*|\b)|[A-Z]?[a-z]+[0-9]*|[A-Z]|[0-9]+/g) ?? [])
        .map((word) => word.toLowerCase())
        .join('-');
}

window.Alpine = Alpine;
Alpine.start();
