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


import $ from 'jquery';
import Ajax from 'core/ajax';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';
import ModalFactory from 'core/modal_factory';
import cardPaginate from 'mod_pokcertificate/cardPaginate';

var selector = {
    'container': '.programs_container',
    'targetElement': '.programs_sectors_list',
    'moreprograms': '[data-action="moreprograms"]'
}
export default class HomePage {
     init()  {
        let sector=0;
        $(selector.targetElement +' li.nav-item').on('click', function(e) {
            e.preventDefault();
            sector = e.target.getAttribute('data-id');
            $(selector.targetElement +' li.nav-item a.active').removeClass('active');
            $(this).children('a').addClass('active');
            var promise = Ajax.call([{
                    methodname: 'local_trainingprogram_programcards',
                    args: {'sector': sector }
                }]);
            promise[0].done(function(resp) {
                Templates.render('mod_pokcertificate/homepage/program_card',{'programs': resp}).done(function(html, js) {
                    Templates.replaceNodeContents(selector.container, html, js);
                });
            });

        });
    }
    morePrograms()  {
        
        $(selector.moreprograms).on('click', function(e) {
            e.preventDefault();
            let pagenumber = e.target.getAttribute('data-pagenumber');
            let limit = e.target.getAttribute('data-limit');
            let numberofprograms = e.target.getAttribute('data-numberofprograms');
            let options = $(this).data('options');
            let perpage = parseInt(pagenumber)+parseInt(limit);
            options.perPage = perpage;
            let dataoptions = $(this).data('dataoptions');
            let filterdatavalue = $(this).data('filterdata');
            
            if(perpage != numberofprograms){
                cardPaginate.reload(options, dataoptions,filterdatavalue);
            }
            if(pagenumber == numberofprograms){
                $(this).hide();
            }
            
             $(this).attr('data-pagenumber', perpage);
        });
    }


    confirmbox(message) {
         ModalFactory.create({
            body: message,
            type: ModalFactory.types.ALERT,
            buttons: {
                cancel: getString('ok'),
            },
            removeOnClose: true,
          })
          .done(function(modal) {
            modal.show();
          });
    }
}
