import cardPaginate from 'mod_pokcertificate/cardPaginate';
import $ from 'jquery';

const Selectors = {
    actions: {
        contentTabs: '[data-action="contentTabs"]'
    },
};
export const init = () => {
    document.addEventListener('click', function(e) {
    let contentTabs = e.target.closest(Selectors.actions.contentTabs);
    if (contentTabs) {
        e.preventDefault();
        e.stopImmediatePropagation();
        let currenttab =contentTabs.getAttribute('data-controls');
        let template =contentTabs.getAttribute('data-template');
        let method =contentTabs.getAttribute('data-method');
        let container =contentTabs.getAttribute('data-container');
        
        var options = { targetID: container,
                        templateName: template,
                        methodName: method,
                        perPage: 5,
                        cardClass: 'col-md-6 col-12',
                        viewType: 'card'};

        $('#global_filter').attr('data-status',currenttab);
        $('#global_filter').attr('data-options',JSON.stringify(options));
        $('input[name=options]').val(JSON.stringify(options));
        var dataoptions = {contextid: 1};
        var filterdata = {workshopid:0, status: currenttab};
        cardPaginate.reload(options, dataoptions,filterdata);
    }
    });
}
