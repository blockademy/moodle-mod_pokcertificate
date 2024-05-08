// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JavaScript for the cardPaginate_preview of the
 * add_random_form class.
 *
 * @module    mod_pokcertificate/cardPaginate
 * @package
 * @copyright 2023 Moodle India Information Solutions Pvt Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'core/ajax',
        'core/str',
        'core/notification',
        'core/templates',
        'mod_pokcertificate/paged_content_factory'
    ],
    function(
        $,
        Ajax,
        Str,
        Notification,
        Templates,
        PagedContentFactory
    ) {

    var ITEMS_PER_PAGE = 6;
    var TEMPLATE_NAME = '';
    var targetID = '';
    var targetRoot = '';

    var SELECTORS = {
        LOADING_ICON_CONTAINER: '[data-region="overlay-icon-container"]',
        PAGINATE_COUNT_CONTAINER: '[data-region="'+targetID+'-count-container"]',
        PAGINATE_LIST_CONTAINER: '[data-region="'+targetID+'-list-container"]'
    };

    var setOptions = function(options){

        TEMPLATE_NAME = options.templateName;
        if(options.hasOwnProperty('targetID')){
            targetID = options.targetID;
            targetRoot = $('#'+targetID);
        }
        if(options.hasOwnProperty('perPage') && typeof(options.perPage) == 'number'){
            ITEMS_PER_PAGE = options.perPage;
        }
        SELECTORS = {
            LOADING_ICON_CONTAINER: '[data-region="overlay-icon-container"]',
            PAGINATE_COUNT_CONTAINER: '[data-region="'+targetID+'-count-container"]',
            PAGINATE_LIST_CONTAINER: '[data-region="'+targetID+'-list-container"]'
        };
    };

    /**
     * Show the loading spinner over the preview section.
     *
     * @param  {jquery} targetRoot The targetRoot element.
     */
    var showLoadingIcon = function(targetRoot) {
        targetRoot.find(SELECTORS.LOADING_ICON_CONTAINER).removeClass('hidden');
    };

    /**
     * Hide the loading spinner.
     *
     * @param  {jquery} targetRoot The targetRoot element.
     */
    var hideLoadingIcon = function(targetRoot) {
        targetRoot.find(SELECTORS.LOADING_ICON_CONTAINER).addClass('hidden');
    };

    /**
     * Send a request to the server for more records.
     *
     * @param  {object[]} options
     * @param  {object[]} dataoptions
     * @param  {object} filterdata
     * @return {promise} Resolved when the preview section has rendered.
     */
    var requestMethod = function(options, dataoptions, filterdata) {
        var request = {
            methodname: options.methodName,
            args: {
                contextid: dataoptions.contextid,
                options: JSON.stringify(options),
                dataoptions: JSON.stringify(dataoptions),
                offset: options.offset,
                limit: options.perPage,
                filterdata: JSON.stringify(filterdata)
            }
        };
        return Ajax.call([request])[0];
    };

    /**
     * Build a paged content widget for records with the given criteria. The
     * criteria is used to fetch more records from the server as the user
     * requests new pages.
     *
     * @param  {object[]} options
     * @param  {object[]} dataoptions
     * @param  {int} totalCount
     * @param  {object[]} firstresponse
     * @param  {object} filterdata
     * @return {promise} A promise resolved with the HTML and JS for the paged content.
     */
    var renderAsPagedContent = function(options, dataoptions, totalCount, firstresponse,filterdata){
        // to control how the records on each page are rendered.
        return PagedContentFactory.createFromAjax(totalCount, ITEMS_PER_PAGE,
            // Callback function to render the requested pages.
            function(pagesData) {
                return pagesData.map(function(pageData) {
                    var offset = pageData.offset;
                    var limit = pageData.limit;
                        options.offset = offset;
                        options.limit = limit;

                        if(offset > 0){
                            return requestMethod(options, dataoptions, filterdata)
                            .then(function(response) {
                                response["cardClass"] = options.cardClass;

                                response["viewtypeCard"] = false;
                                if(options.viewType == "card" || options.viewType == "table"){
                                    response["viewtypeCard"] = true;
                                }
                                return Templates.render(options.templateName, {response: response});
                            })
                            .fail(Notification.exception);
                        } else {
                                firstresponse["cardClass"] = options.cardClass;

                                firstresponse["viewtypeCard"] = false;
                                if(options.viewType == "card" || options.viewType == "table"){
                                    firstresponse["viewtypeCard"] = true;
                                }
                            return Templates.render(options.templateName, {response: firstresponse});
                        }
                    // }
                });
            },
        // Config to set up the paged content.
        {
            controlPlacementBottom: true,
            eventNamespace: 'paginate-paged-content-'+options.targetID,
            persistentLimitKey: 'paginate-paged-content-limit-key'
        }
        );
    };

    /**
     * Re-render the preview section based on the provided filter criteria.
     *
     * @param  {object[]} options
     * @param  {object[]} dataoptions
     * @param  {object} filterdata
     * @return {promise} Resolved when the preview section has rendered.
     */
    var reload = function(options, dataoptions,filterdata) {
        //alert("hi");
        setOptions(options);

        // Show the loading spinner to tell the user that something is happening.
        showLoadingIcon(targetRoot);

        // Load the first set of records.
        options.offset = 0;
        return requestMethod(options, dataoptions,filterdata)
            .then(function(response) {

                var totalCount = response.totalcount;
                var records = response.records;
                if (records.length) {
                    // We received some records so render them as paged content
                    // with a paging bar.
                    return renderAsPagedContent(options, dataoptions, totalCount, response, filterdata);
                } else {
                    // If we didn't receive any records then we can return empty
                    // HTML and JS to clear the preview section.
                    // console.log(response.extraparams.nodata);
                    if(response.nodata){
                        return Templates.render(options.templateName, {response: response});
                    }else{
						var name=Str.get_string('no_data_available', 'mod_pokcertificate');
						return name.then(function(s) {
                            return Templates.render('mod_pokcertificate/no-data', {name:s});
						});
                    }

                }
            })
            .then(function(html, js) {
                // Show the user the records set.
                targetRoot = $('#'+options.targetID);
                var paginatelistcontainer = '[data-region="'+options.targetID+'-list-container"]';
                var container = targetRoot.find(paginatelistcontainer);
                Templates.replaceNodeContents(container, html, js);
                return;
            })
            .always(function() {
                targetRoot = $('#'+options.targetID);
                hideLoadingIcon(targetRoot);
            })
            .fail(Notification.exception);

    };

    //added for the filtering the data
    var filteringData = function(e,submitid) {
        var formdata =  $("form#"+submitid+"").serializeArray();
        values = [];
        filterdatavalue = [];
        $.each(formdata, function (i, field) {
            valuedata = [];
            if(field.name != '_qf__filters_form' && field.name != 'sesskey'){
                if(field.name == 'options' || field.name == 'dataoptions'){
                    values[field.name] = field.value;
                }else{
                    var str = field.name;
                    if(str.indexOf('[]') != -1){
                        field.name = str.substring(0, str.length - 2);
                    }
                    if(field.value != '_qf__force_multiselect_submission'){
                        if(field.name in filterdatavalue){
                            filterdatavalue[field.name] = filterdatavalue[field.name]+','+field.value;
                        }else{
                            filterdatavalue[field.name] = field.value;
                        }
                    }
                }

            }
        });
        var filtervalue = $('#global_filter').val();
        if(filtervalue){
            filterdatavalue[$('#global_filter').attr('name')] = filtervalue;
        }
        optionsparsondata     = JSON.parse(values['options']);
        dataoptionsparsondata = JSON.parse(values['dataoptions']);
        // filterdataparsondata  =  Object.assign({}, filterdatavalue);
        filterdataparsondata = $.extend({}, filterdatavalue);
        $('#global_filter').attr('data-filterdata', JSON.stringify(filterdataparsondata));
        return reload(optionsparsondata, dataoptionsparsondata,filterdataparsondata);
    };

    //added for the reset the data
    var resetingData = function(e,submitid) {
        var formdata =  $("form#"+submitid+"").serializeArray();
        values = [];
        filterdatavalue = [];
        $.each(formdata, function (i, field) {
            valuedata = [];
            if(field.name != '_qf__filters_form' && field.name != 'sesskey'){
                if(field.name == 'options' || field.name == 'dataoptions'){
                    values[field.name] = field.value;
                }
            }
        });
        var filtervalue = $('#global_filter').val();
        if(filtervalue){
            filterdatavalue[$('#global_filter').attr('name')] = filtervalue;
        }
        optionsparsondata     = JSON.parse(values['options']);
        dataoptionsparsondata = JSON.parse(values['dataoptions']);
        filterdataparsondata = $.extend({}, filterdatavalue);
        $('#global_filter').attr('data-filterdata', '[]');

        reload(optionsparsondata, dataoptionsparsondata, filterdataparsondata);
        $("form#"+submitid+"")[0].reset();

        // $(".tag-info").html("");
        $("div.form-autocomplete-selection").html("");
        // $("div.form-autocomplete-selection").removeClass("tag-info");
        // $("div.form-autocomplete-selection").removeClass("tag");

        // // Unset the value from text and select elements.
        // // $("#filters_form .custom-select option:selected").prop('selected', false);
        // // $("#filters_form input[type='text']").html("");
        // // $("#filters_form input[type='text']").val("");


        // $("#id_programname").val("");
        // $("select[name='specialization[]'] option:selected").prop('selected', false);
        // $("select[name='specialization[]']").parent().find('.badge-secondary').html('');
        // $("select[name='trainingproject[]'] option:selected").prop('selected', false);
        // $("select[name='trainingproject[]']").parent().find('.badge-secondary').html('');
    };
    return {
        reload: reload,
        showLoadingIcon: showLoadingIcon,
        hideLoadingIcon: hideLoadingIcon,
        filteringData:filteringData,
        resetingData:resetingData
    };
});
