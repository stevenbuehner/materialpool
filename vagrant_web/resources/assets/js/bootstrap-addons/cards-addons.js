(function ($) {

    $.fn.extend({
        card: function (options) {
            options = $.extend({}, $.MyBootstrapCard.defaults, options);

            this.each(function () {
                new $.MyBootstrapCard(this, options);
            });
            return this;
        }
    });


    // ctl is the element, options is the set of defaults + user options
    $.MyBootstrapCard = function (element, options) {
        var $el   = $(element);
        this.link = $el.data(options.linkdata) || '#';

        $el.on("click", $.proxy(this.handleClick, this))
    };

    $.MyBootstrapCard.prototype.handleClick = function (e) {
        e.preventDefault();
        window.location.href = this.link;
    };

    // option defaults
    $.MyBootstrapCard.defaults = {
        linkdata: 'url'
    };


})(jQuery);