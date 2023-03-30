<div class="ab_product_carousel">
    {foreach $products as $product}
        <div class="ab_item">
            <div class="ab_header">{$product.category_name}</div>
                <div class="ab_products tinySlider">
                    {foreach $product.products as $item}
                        {include file='module:ab_product_carousel/views/templates/hook/product.tpl' product=$item}
                    {/foreach}
                </div>
            <div class="ab_footer">
                <a href="{$product.category_url}" class="ab_button">{l s='więcej z tej kategorii' d='Modules.Product_Carousel.Shop'}</a>
            </div>
        </div>
    {/foreach}
</div>