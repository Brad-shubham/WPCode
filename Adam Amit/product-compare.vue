<template>
  <main>
    <view-preview
      v-if="'preview' === this.view"
      :products="products"
      :products_clean="products_clean"
      :compare="compare"
      :preview="preview"
      :show_prices="show_prices"
      @onPreviewProduct="previewProduct"
      @onSelectProduct="selectProduct"
      @onCompareProduct="compareProduct"
    >
      <slot></slot>
    </view-preview>

    <view-compare
      v-if="'compare' === this.view"
      :products="products"
      :compare="compare"
      :headers="headers"
      :show_prices="show_prices"
    ></view-compare>

    <compare-bar
      v-if="
        compare.items.length &&
        ('grid' === this.view || 'preview' === this.view)
      "
      :products="products"
      :compare="compare"
      :show_prices="show_prices"
    ></compare-bar>
  </main>
</template>

<script>
/**
 * WooCommerce Products Compare
 */
export default {
  props: {
    products: Object,
    headers: Object,
    order: String,
    show_prices: String,
  },
  emits: ["onPreviewProduct", "onSelectProduct", "onCompareProduct"],
  data: function () {
    return {
      compare: {
        max: 3,
        items: [],
      },
      preview: null,
      view: "grid",
    };
  },

  computed: {
    products_clean: function () {
      var items = [];

      for (var prop in this.products) {
        items.push(this.products[prop]);
      }

      items.sort(function (a, b) {
        if (a.order < b.order) {
          return -1;
        } else if (a.order > b.order) {
          return 1;
        }

        return 0;
      });

      return items;
    },
  },

  methods: {
    /**
     * Add/Remove Product from Compare
     */
    compareProduct: function (id) {
      // Already in the compare array? Remove it!
      if (this.compare.items.includes(id)) {
        this.compare.items.splice(this.compare.items.indexOf(id), 1);
      } else {
        this.compare.items.push(id);
      }
    },

    /*
     * Select Product
     */
    selectProduct: function (id) {
      this.$root.spFields["product_id"] = id;
      this.updateView("review");
    },

    /*
     * Remove Product
     */
    removeProduct: function () {
      this.$root.spFields["product_id"] = null;
      this.updateView("grid");
    },

    /*
     * Update View
     */
    updateView: function (view) {
      this.view = view;
      document.documentElement.scrollTop = 0;
    },

    /*
     * Preview Product
     */
    previewProduct: function (id) {
      this.preview = id;
      this.updateView("preview");
    },
  },
};
</script>
