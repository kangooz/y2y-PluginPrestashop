{*
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
*}

<div class="panel">
    <h3><i class="icon icon-tags"></i> {l s='Settings' mod='y2ypssm'}</h3>
    <form class="form-horizontal y2ypssm-configuration-form" action="{$submitUrl}" method="post">
        {foreach from=$formFields key=key item=value}
            {if $value.type == 'text'}
                <div class="form-group">
                    <label for="{$key}" class="col-sm-2 control-label">{$value.desc}</label>
                    <div class="col-sm-10">
                        <input type="{$value.type}" class="form-control" id="{$key}" name="{$key}" value="{$configValues.$key}">
                    </div>
                </div>
            {elseif $value.type == 'number' && $value.desc == 'Timeout'}
                <div class="form-group">
                    <label for="{$key}" class="col-sm-2 control-label">{$value.desc}</label>
                    <div class="col-sm-10">
                        <input type="{$value.type}" class="form-control" id="{$key}" name="{$key}" value="{$configValues.$key|default:0}" min="0" step="0.5">
                    </div>
                </div>
            {elseif $value.type == 'number' && $value.desc == 'Rows'}
                <div class="form-group">
                    <label for="{$key}" class="col-sm-2 control-label">{$value.desc}</label>
                    <div class="col-sm-10">
                        <input type="{$value.type}" class="form-control" id="{$key}" name="{$key}" value="{$configValues.$key|default:5}" min="1" step="1">
                    </div>
                </div>
            {elseif $value.type == 'textarea'}
                <div class="form-group">
                    <label for="{$key}" class="col-sm-2 control-label">{$value.desc}</label>
                    <div class="col-sm-10">
                        <textarea class="form-control" id="{$key}" name="{$key}" rows="2">{$configValues.$key}</textarea>
                    </div>
                </div>
            {elseif $value.type == 'checkbox' && $value.desc == 'Inline Calendar'}
                <div class="form-group">
                    <label for="{$key}" class="col-sm-2 control-label">{$value.desc}</label>
                    <div class="col-sm-10">
                        <input type="checkbox" name="{$key}" value="yes" 
                            {if $configValues.$key == "yes"}checked="checked"{/if}>
                    </div>
                </div>
            {/if}
        {/foreach}

        <div class="table-responsive">
            <table class="table table-condensed table-bordered table-striped text-center table">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th class="text-center">{l s='Opening hours' mod='y2ypssm'}</th>
                        <th class="text-center">{l s='Lunch time' mod='y2ypssm'}</th>
                        <th class="text-center">{l s='Closed day' mod='y2ypssm'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$daysOfWeek key=key item=value}
                        <tr>
                            <td>
                                {$value}
                            </td>
                            <td>
                                <div class="col-md-2 col-md-offset-3">
                                    <input type='text' name="Y2YPSSM_OPENING_HOURS[{$key}]" id="Y2YPSSM_OPENING_HOURS[{$key}]" 
                                           value="{$configValues.Y2YPSSM_OPENING_HOURS.$key}" class="y2ypssm-timepicker" data-field="time" >
                                    
                                </div>
                                <div class="col-md-2">
                                    {l s='until' mod='y2ypssm'}
                                </div>
                                <div class="col-md-2">
                                    <input type='text' name="Y2YPSSM_CLOSING_HOURS[{$key}]" id="Y2YPSSM_CLOSING_HOURS[{$key}]" 
                                           value="{$configValues.Y2YPSSM_CLOSING_HOURS.$key}" class="y2ypssm-timepicker" data-field="time" >
                                </div>
                            </td>
                            <td>
                                <div class="col-md-2 col-md-offset-3">
                                    <input type='text' name="Y2YPSSM_LUNCH_TIME_BEGIN[{$key}]" id="Y2YPSSM_LUNCH_TIME_BEGIN[{$key}]" 
                                           value="{$configValues.Y2YPSSM_LUNCH_TIME_BEGIN.$key}" class="y2ypssm-timepicker" data-field="time" readonly>
                                    
                                </div>
                                <div class="col-md-2">
                                    {l s='until' mod='y2ypssm'}
                                </div>
                                <div class="col-md-2">
                                    <input type='text' name="Y2YPSSM_LUNCH_TIME_END[{$key}]" id="Y2YPSSM_LUNCH_TIME_END[{$key}]" 
                                           value="{$configValues.Y2YPSSM_LUNCH_TIME_END.$key}" class="y2ypssm-timepicker" data-field="time" readonly>
                                </div>
                            </td>
                            <td>
                                <div class="col-md-12">
                                    <input type="checkbox" name="Y2YPSSM_CLOSED_DAY[{$key}]" value="yes" 
                                        {if $configValues.Y2YPSSM_CLOSED_DAY.$key == "yes"}checked="checked"{/if}>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            <div  class="y2ypssm-timepicker-holder"></div>
        </div>

        <div class="form-group">
            <div class="col-sm-10">
                <button type="submit" class="btn btn-success" name="submit{$moduleName}">
                    <i class="icon icon-save"></i>
                    {l s='Save' mod='y2ypssm'}
                </button>
            </div>
        </div>
    </form>
</div>