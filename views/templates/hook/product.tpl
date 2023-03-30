{**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{block name='product_miniature_item'}
  <article class="ab_product_miniature" data-id-product="{$product.id_product}" data-id-product-attribute="{$product.id_product_attribute}" itemscope itemtype="http://schema.org/Product">
    <div class="ab_thumbnail">
      {block name='product_thumbnail'}
        {if $product.cover}
          <a href="{$product.canonical_url}" class="ab_thumbnail_href">
            <img
              class="ab_image"
              src="{$product.cover.bySize.home_default.url}"
              alt="{if !empty($product.cover.legend)}{$product.cover.legend}{else}{$product.name|truncate:30:'...'}{/if}"
            />
          </a>
        {else}
          <a href="{$product.canonical_url}" class="ab_thumbnail_href">
            <img src="{$urls.no_picture_image.bySize.home_default.url}" class="ab_image" />
          </a>
        {/if}
      {/block}
    </div>
    <div class="ab_product_name">
        {block name='product_name'}
          <h3 class="ab_product_title" itemprop="name">
            <a href="{$product.canonical_url}">{$product.name|truncate:30:'...'}</a>
          </h3>
        {/block}
    </div>

    {block name='product_price_and_shipping'}
      {if $product.show_price}
        <div class="ab_product_prices">
          {if $product.has_discount}
            {* {hook h='displayProductPriceBlock' product=$product type="old_price"}

            <span class="sr-only">{l s='Regular price' d='Shop.Theme.Catalog'}</span> *}
            {* <span class="regular-price">{$product.regular_price}</span> *}
            {if $product.discount_type === 'percentage'}
              <span class="discount-percentage discount-product">{$product.discount_percentage}</span>
            {elseif $product.discount_type === 'amount'}
              <span class="discount-amount discount-product">{$product.discount_amount_to_display}</span>
            {/if}
          {/if}

          {hook h='displayProductPriceBlock' product=$product type="before_price"}
          <span itemprop="price" class="price">{$product.price}</span>

          {hook h='displayProductPriceBlock' product=$product type='unit_price'}

          {hook h='displayProductPriceBlock' product=$product type='weight'}
          {if !$configuration.is_catalog}
            <form action="{$urls.pages.cart}" method="post" class="ad_add_cart">
              <input type="hidden" name="token" value="{$static_token}">
              <input type="hidden" name="id_product" value="{$product.id_product}" id="product_page_product_id">
              <input type="hidden" name="id_product_attribute" value="{$product.cache_default_attribute}">
              <input
                    type="number"
                    name="qty"
                    class="quantity_wanted"
                    value="1"
                    class="input-group"
                    min="{$product.minimal_quantity}"
                    aria-label="{l s='Quantity' d='Shop.Theme.Actions'}"
                  >
                <button
                      class="add-to-cart"
                      data-button-action="add-to-cart"
                      type="submit"
                      {if !$product.add_to_cart_url}
                        disabled
                      {/if}
                    >
                    <i class="material-icons shopping-cart">&#xE547;</i>
                </button>
            </form>
          {/if}  
        </div>
      {/if}
    {/block}
    
  </article>
{/block}
