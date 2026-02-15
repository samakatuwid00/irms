/**
 * Handles multi-author tag input functionality
 */
export class MultiAuthorInput {
    constructor(inputId, wrapperId, hiddenInputId) {
        this.authorInput = document.getElementById(inputId);
        this.wrapper = document.getElementById(wrapperId);
        this.hiddenInput = document.getElementById(hiddenInputId);
        this.authors = [];

        this.init();
    }

    init() {
        if (!this.authorInput || !this.wrapper || !this.hiddenInput) return;

        this.authorInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.addAuthor(this.authorInput.value.trim());
            }
        });
    }

    /**
     * Add an author tag
     */
    addAuthor(name) {
        if (!name || this.authors.includes(name)) return;

        this.authors.push(name);
        this.updateHiddenInput();

        const tag = this.createTag(name);
        this.wrapper.insertBefore(tag, this.authorInput);
        this.authorInput.value = '';
    }

    /**
     * Remove an author tag
     */
    removeAuthor(name) {
        this.authors = this.authors.filter(a => a !== name);
        this.updateHiddenInput();
    }

    /**
     * Create author tag element
     */
    createTag(name) {
        const tag = document.createElement('span');
        tag.className = 'flex items-center bg-blue-100 text-blue-700 px-2 py-1 rounded text-sm';

        tag.innerHTML = `
            ${name}
            <button type="button" class="ml-1 text-blue-700 hover:text-red-600">&times;</button>
        `;

        const removeButton = tag.querySelector('button');
        removeButton.addEventListener('click', () => {
            this.removeAuthor(name);
            tag.remove();
        });

        return tag;
    }

    /**
     * Update hidden input with JSON array
     */
    updateHiddenInput() {
        this.hiddenInput.value = JSON.stringify(this.authors);
    }
}
