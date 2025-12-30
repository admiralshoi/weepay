/**
 * JavaScript Translator Class
 * Synced with PHP Translate class (classes/lang/Translate.php)
 * Data populated from PHP DA class at runtime
 */

class Translator {
    constructor() {
        this.lang = 'DA';
        this.translations = {
            WORD: {},
            CONTEXT: {}
        };
    }

    /**
     * Set language
     * @param {string} lang
     */
    setLang(lang) {
        this.lang = lang;
    }

    /**
     * Get current language
     * @returns {string}
     */
    getLang() {
        return this.lang;
    }

    /**
     * Load translations from PHP data
     * This should be called with data from the PHP DA class
     * @param {Object} data - Object with WORD and CONTEXT properties
     */
    loadTranslations(data) {
        if (data.WORD) {
            this.translations.WORD = data.WORD;
        }
        if (data.CONTEXT) {
            this.translations.CONTEXT = data.CONTEXT;
        }
    }

    /**
     * Translate a single word
     * @param {string|number|null} query
     * @returns {string}
     */
    word(query) {
        if (empty(query)) return query;

        const key = String(query).toLowerCase();

        if (!this.translations.WORD) return query;

        return this.translations.WORD[key] || query;
    }

    /**
     * Translate using context path (e.g., "checkout.status.completed")
     * @param {string|number|null} query
     * @returns {string}
     */
    context(query) {
        if (empty(query)) return query;

        if (!this.translations.CONTEXT) return query;

        const key = String(query);
        const parts = key.split('.').map(p => p.toLowerCase());

        let result = this.translations.CONTEXT;
        for (const part of parts) {
            if (result && typeof result === 'object' && part in result) {
                result = result[part];
            } else {
                return query;
            }
        }

        return typeof result === 'string' ? result : query;
    }

    /**
     * Translate words within a sentence
     * Preserves whitespace and punctuation
     * @param {string|null} query
     * @returns {string}
     */
    sentence(query) {
        if (empty(query)) return query;

        if (!this.translations.WORD) return query;

        // Split on whitespace but preserve delimiters
        const tokens = query.split(/(\s+)/);

        const translated = tokens.map(token => {
            // Extract word without punctuation
            const match = token.match(/^([^\p{L}]*)([\p{L}]+)([^\p{L}]*)$/u);

            if (match) {
                const [, prefix, word, suffix] = match;
                const lower = word.toLowerCase();

                if (this.translations.WORD[lower]) {
                    return prefix + this.translations.WORD[lower] + suffix;
                }
            }

            return token;
        });

        return empty(translated) ? query : translated.join('');
    }
}

// Create global instance
window.Translate = new Translator();

// Auto-load translations if translationData is available (populated by PHP)
if (typeof translationData !== 'undefined') {
    window.Translate.loadTranslations(translationData);
}
