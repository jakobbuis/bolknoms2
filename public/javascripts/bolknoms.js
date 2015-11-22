App = {

    /**
     * Bootstrap the application, set event handlers, etc.
     * @return {undefined}
     */
    init: function() {
        // Common functionality enhancements
        $('.auto-submit').on('change', App.autoSubmit);
        $('.confirm-intent').on('click', App.confirmIntent);
    },

    /**
     * Automatically submmit the parent form when an input changes
     * @param  {Event} event
     * @return {undefined}
     */
    autoSubmit: function(event) {
        this.form.submit();
    },

    /**
     * Ask a user for confirmation before following a click
     * @param  {Event} event
     * @return {undefined}
     */
    confirmIntent: function(event) {
        if (!confirm('Weet je het zeker?')) {
            event.preventDefault();
        }
    },
};

// Start the application
$(document).on('ready', App.init);
