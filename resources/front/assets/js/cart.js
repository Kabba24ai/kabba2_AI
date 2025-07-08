window.CartStorage = (function(){
    const CART_KEY = 'kabba_cart';

    function getCart() {
        return JSON.parse(localStorage.getItem(CART_KEY)) || [];
    }

    function setCart(cart) {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
    }


    function addOrUpdateItem(productData) {
        let cart = getCart();

        // Remove any existing item with same product_unique_id
        cart = cart.filter(item => item.product_unique_id !== productData.product_unique_id);

        // Add the new/updated item from the backend
        cart.push(productData);

        setCart(cart);
        return cart;
    }

    function removeItemByUniqueId(uniqueId) {
        let cart = getCart();
        cart = cart.filter(item => item.product_unique_id !== uniqueId);
        setCart(cart);
        return cart;
    }

    function removeItemByIndex(index) {
        let cart = getCart();
        if (index >= 0 && index < cart.length) {
            cart.splice(index, 1);
            setCart(cart);
        }
        return cart;
    }

    function clearCart() {
        localStorage.removeItem(CART_KEY);
    }

    function getTotalQuantity() {
        return getCart().reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
    }

    return {
        getCart,
        setCart,
        addOrUpdateItem,
        clearCart,
        getTotalQuantity,
        removeItemByUniqueId,
        removeItemByIndex
    };
})();

