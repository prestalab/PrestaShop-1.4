{*
* 2007-2013 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if isset($cms) && ($content_only == 0)}
	{include file="$tpl_dir./breadcrumb.tpl"}
{/if}
{if isset($cms) && !isset($category)}
	{if !$cms->active}
		<br />
		<div id="admin-action-cms">
			<p>{l s='This CMS page is not visible to your customers.'}
			<input type="hidden" id="admin-action-cms-id" value="{$cms->id}" />
			<input type="submit" value="{l s='Publish'}" class="exclusive" onclick="submitPublishCMS('{$base_dir}{$smarty.get.ad|escape:'htmlall':'UTF-8'}', 0)"/>			
			<input type="submit" value="{l s='Back'}" class="exclusive" onclick="submitPublishCMS('{$base_dir}{$smarty.get.ad|escape:'htmlall':'UTF-8'}', 1)"/>			
			</p>
			<div class="clear" ></div>
			<p id="admin-action-result"></p>
			</p>
		</div>
	{/if}
	<h1>{$cms->title|escape:'htmlall':'UTF-8'}{if $cms->comment} <span>{dateFormat date=$cms->date_add|escape:'html':'UTF-8' full=0}</span>{/if}</h1>
	<div class="rte{if $content_only} content_only{/if}">
		{$cms->content}
	</div>
	{if $cms->comment}
	<div class="cms_categories_list">
		<ul>
			<li><strong> {l s='Posted on'}</strong></li>
			{foreach from=$cms_categories item=category name=category_list}
				<li><a href="{$category.link}" title="{$category.name}">{$category.name}</a>{if !$smarty.foreach.category_list.last},{/if}</li>
			{/foreach}
		</ul>
		<div class="clear"></div>
	</div>
	{/if}
	{if $products}
		{include file="$tpl_dir./product-list.tpl" products=$products}
	{/if}
	{$HOOK_CMS_FOOTER}
{elseif isset($category)}
{capture name=path}{$category->name}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
	<div class="block-cms">
		<h1><a href="{if $category->id eq 1}{$base_dir}{else}{$link->getCategoryLink($category->id, $category->link_rewrite)}{/if}">{$category->name|escape:'htmlall':'UTF-8'}</a></h1>
		{if isset($sub_category) && !empty($sub_category)}	
			<h4>{l s='List of sub categories in '}{$category->name}{l s=':'}</h4>
			<ul class="bullet">
				{foreach from=$sub_category item=subcategory}
					<li>
						<a href="{$link->getCMSCategoryLink($subcategory.id_cms_category, $subcategory.link_rewrite)|escape:'htmlall':'UTF-8'}">{$subcategory.name|escape:'htmlall':'UTF-8'}</a>
					</li>
				{/foreach}
			</ul>
		{/if}
		{if isset($cms_pages) && !empty($cms_pages)}
		<h4>{l s='List of pages in'}&nbsp;{$category->name}{l s=':'}</h4>
			<ul id="product_list" class="clear">
				{foreach from=$cms_pages item=cmspages}
					<li class="clearfix">
						<div>
							{if $cmspages.image}<a href="{$link->getCMSLink($cmspages.id_cms, $cmspages.link_rewrite)|escape:'htmlall':'UTF-8'}" class="product_img_link" title="{$cmspages.title|escape:'htmlall':'UTF-8'}">
								<img src="{$img_ps_dir}cms/{$cmspages.id_cms}.jpg" alt="{$cmspages.title|escape:'htmlall':'UTF-8'}" width="129" height="129" />
							</a>{/if}
							<h3><a href="{$link->getCMSLink($cmspages.id_cms, $cmspages.link_rewrite)|escape:'htmlall':'UTF-8'}" title="{$cmspages.title|escape:'htmlall':'UTF-8'}">{$cmspages.title|escape:35:'...'|escape:'htmlall':'UTF-8'}</a></h3>
							<p class="product_desc"><a href="{$link->getCMSLink($cmspages.id_cms, $cmspages.link_rewrite)|escape:'htmlall':'UTF-8'}" title="{$cmspages.description_short|strip_tags:'UTF-8'|truncate:360:'...'}">{$cmspages.description_short}</a></p>
						</div>
					</li>
					{*<li>
						<a href="{$link->getCMSLink($cmspages.id_cms, $cmspages.link_rewrite)|escape:'htmlall':'UTF-8'}">{$cmspages.meta_title|escape:'htmlall':'UTF-8'}</a>
					</li>*}
				{/foreach}
			</ul>
		{/if}
	</div>
{else}
	{l s='This page does not exist.'}
{/if}
<br />
