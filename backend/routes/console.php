<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:delete-valorant-genshin', function () {
    $products = \App\Models\Product::withTrashed()
        ->where(function ($q) {
            $q->where('name', 'LIKE', '%valorant%')
              ->orWhere('name', 'LIKE', '%genshin%')
              ->orWhere('slug', 'LIKE', '%valorant%')
              ->orWhere('slug', 'LIKE', '%genshin%')
              ->orWhere('sku', 'LIKE', '%VAL%')
              ->orWhere('sku', 'LIKE', '%GI%');
        })
        ->get();

    $this->info("Found " . $products->count() . " products matching Valorant / Genshin.");

    foreach ($products as $p) {
        $this->line("- Deleting product: {$p->name} (ID: {$p->id})");
        $variantIds = \App\Models\ProductVariant::where('product_id', $p->id)->pluck('id')->all();
        $accountIds = \App\Models\GameAccount::where('product_id', $p->id)->pluck('id')->all();

        $orderItemIds = \App\Models\OrderItem::where('product_id', $p->id)
            ->when(!empty($accountIds), fn($q) => $q->orWhereIn('game_account_id', $accountIds))
            ->when(!empty($variantIds), fn($q) => $q->orWhereIn('variant_id', $variantIds))
            ->pluck('id')
            ->all();

        if (!empty($orderItemIds)) {
            \App\Models\ServiceRequest::whereIn('order_item_id', $orderItemIds)->delete();
            \App\Models\WarrantyClaim::whereIn('order_item_id', $orderItemIds)->delete();
            \App\Models\OrderItem::whereIn('id', $orderItemIds)->delete();
        }

        \App\Models\CartItem::where(function ($q) use ($accountIds, $variantIds) {
            if (!empty($accountIds)) {
                $q->whereIn('game_account_id', $accountIds);
            }
            if (!empty($variantIds)) {
                $q->orWhereIn('variant_id', $variantIds);
            }
        })->delete();

        \App\Models\Review::where('product_id', $p->id)->delete();
        \App\Models\GameAccount::where('product_id', $p->id)->delete();
        \App\Models\ProductVariant::where('product_id', $p->id)->delete();
        \App\Models\ProductImage::where('product_id', $p->id)->delete();
        \App\Models\ProductAttributeValue::where('product_id', $p->id)->delete();
        $p->forceDelete();
    }

    $categories = \App\Models\Category::where(function ($q) {
        $q->where('name', 'LIKE', '%valorant%')
          ->orWhere('name', 'LIKE', '%genshin%')
          ->orWhere('slug', 'LIKE', '%valorant%')
          ->orWhere('slug', 'LIKE', '%genshin%');
    })->get();

    $this->info("Found " . $categories->count() . " categories matching Valorant / Genshin.");
    foreach ($categories as $c) {
        $this->line("- Deleting category: {$c->name} (ID: {$c->id})");
        // Ensure no products remain before deleting category
        \App\Models\Product::where('category_id', $c->id)->delete();
        $c->delete();
    }

    $this->info("Successfully deleted all Valorant and Genshin data from DB!");
})->purpose('Delete Valorant and Genshin products and categories');
