/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

(function($) {
    $(document).ready(function(){
        if($(document).find('input.delivery_option_radio:checked').length > 0){
            $triggerTarget = $(document).find('input.delivery_option_radio:checked').each(function(index){
                var key = $(this).data('key');
                var id_address = parseInt($(this).data('id_address'));
                if (orderProcess == 'order' && key && id_address)
                        updateExtraCarrier(key, id_address);
                else if(orderProcess == 'order-opc' && typeof updateCarrierSelectionAndGift !== 'undefined')
                        updateCarrierSelectionAndGift();
           });
        }
        $("#y2ypssm_delivery_date").datepicker();
        //$('#delivery_date').attr('data-field', 'datetime');
        $( ".y2ypssm-timepicker-holder" ).DateTimePicker({
            mode: "time",
            language: 'fr',
            timeFormat: 'HH:mm',
            minuteInterval: 15,
            roundOffMinutes: true
        });
    });
})(jQuery);