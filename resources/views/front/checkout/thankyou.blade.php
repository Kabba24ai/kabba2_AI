@extends('front.layouts.app')

@section('title', $title)

@section('content')
<section class="bg-white py-10 px-4 md:px-10 pt-130 pb-60">
   <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-6">
   <!-- LEFT SECTION: Confirmation + Customer Info -->
   <div class="lg:w-2/3 space-y-6">
      <!-- Confirmation Box -->
      <div class="flex items-center gap-4 pt-6">
         <div class="text-green-500 text-4xl">
            <img src="{{ asset('storage/front/images/thankyou-right.png') }}" alt="" class="h-full md:w-20 w-20">
         </div>
         <div>
            <h2 class="text-xl font-bold text-black">Your order has been successfully placed</h2>
            <p class="text-gray-600">Thank you for your business!</p>
         </div>
      </div>
      <!-- Customer Info -->
      <div class="space-y-2">
         <h3 class="text-lg font-semibold">Customer information</h3>
         <p><span class="font-normal">Full name:</span> <span class="text-custom-blue ml-5">Nipa Soni</span></p>
         <p><span class="font-normal">Phone:</span> <span class="text-custom-blue ml-5">(888) 8 88-8888</span></p>
         <p><span class="font-normal">Email:</span> <span class="text-custom-blue ml-5">nipavadhavana@gmail.com</span></p>
         <p><span class="font-normal">Address:</span> <span class="text-custom-blue ml-5">County Road, Pearland, Texas, US, 3333333</span></p>
         <p><span class="font-normal">Payment method:</span> <span class="text-custom-blue ml-5">Cash on delivery (COD)</span></p>
         <p><span class="font-normal">Payment status:</span> <span class="text-yellow-500 font-semibold ml-5">PENDING</span></p>
      </div>
      <!-- Button -->
      <div>
         <a href="/" class="inline-block bg-yellow-400 text-black font-medium px-6 py-3 rounded-lg hover:bg-yellow-500 transition">
         CONTINUE SHOPPING
         </a>
      </div>
   </div>
   <template id="singleItemTpl">
      <div class="flex gap-4 mb-10">
         <div class="relative">
            <img src="assets/images/product/boom-lift-v2.png"
               alt="Product Image"
               class="w-24 h-24 object-cover border-1 border-gray-200 rounded" />
            <span class="bg-gray-200 w-6 h-6 rounded-full absolute -top-2 -right-1 text-xs text-center content-center font-bold">1</span>
         </div>
         <div class="flex flex-col justify-between w-full">
            <div class="flex justify-between items-start">
               <p class="font-normal text-sm text-black">Boom Lift Rental - Weekend Special</p>
               <span class="text-sm font-normal text-black ml-5">$515.00</span>
            </div>
            <div class="text-sm text-gray-600 mt-1">
               <p>Schedule Date: <span class="">06/30/2025</span></p>
               <p class="font-normal ">Total: Including Options <span class="ml-3">$515.00</span></p>
               <p class="font-normal ">Options Total:<span class="ml-3">+ $216.00</span></p>
               <ul class="list-disc ml-5 mt-1 space-y-1 text-xs">
                  <li>Safety Harness - Rent</li>
                  <li>Damage Waiver</li>
                  <li>Delivery</li>
                  <li>Pick Up</li>
               </ul>
            </div>
         </div>
      </div>
   </template>
   <!-- RIGHT SECTION: Order Summary -->
   <div id="orderSummary" class="lg:w-1/3 border-l pl-6 space-y-6">
      <h3 class="text-md text-gray-700 font-normal">
         Order number: <span class="font-normal text-black">#10005422</span>
      </h3>
      <!-- PRODUCT ITEMS will inject HERE -->
      <div id="itemsContainer"></div>
      <!-- Totals -->
      <div id="totalsBlock" class="text-sm border-t pt-4 space-y-2">
         <div class="flex justify-between">
            <span>Subtotal:</span>
            <span class="font-semibold">$0.00</span>
         </div>
         <div class="flex justify-between">
            <span>Tax:</span>
            <span class="font-semibold">$0.00</span>
         </div>
         <div class="flex justify-between text-lg font-bold text-black border-t pt-2">
            <span>Total:</span>
            <span>$0.00</span>
         </div>
      </div>
   </div>
</section>


@endsection

@push('js')
<script>
document.addEventListener("DOMContentLoaded", function() {
  const cart = JSON.parse(localStorage.getItem("rental_cart")) || [];
  const tpl          = document.getElementById("singleItemTpl");
  const itemsEl      = document.getElementById("itemsContainer");
  const totalsBlock  = document.getElementById("totalsBlock");

  if (!tpl || !itemsEl || !totalsBlock) return;

  let subtotal = 0;
  let totalTax = 0;

  // Clear any static children:
  itemsEl.innerHTML = "";

  // Render each cart item
  cart.forEach(item => {
    const clone = tpl.content.cloneNode(true);
    const root  = clone.querySelector("div.flex.gap-4");

    const qty       = parseInt(item.qty)      || 1;
    const basePrice = parseFloat(item.base_price) || 0;
    const taxRate   = parseFloat(item.tax_rate)   || 0;  // decimal, e.g. 0.0975

    // calculate per-item totals
    let addonTotal = 0;
    (item.addons || []).forEach(a => {
      const p = parseFloat(a.price) || 0;
      const c = (a.charged||"").toLowerCase();
      addonTotal += c === "unlimited" ? p * qty : p;
    });
    const deliveryFee = parseFloat(item.delivery_fee) || 0;
    const itemTotal   = (basePrice * qty) + addonTotal + deliveryFee;
    const itemTax     = +(itemTotal * taxRate).toFixed(2);

    subtotal += itemTotal;
    totalTax += itemTax;

   
    root.querySelector("img").src = item.image;
    root.querySelector("span.font-bold").textContent = qty;
    root.querySelector("p.font-normal.text-sm").textContent = item.name;
    root.querySelector("p.font-normal.text-sm + span")
        .textContent = `$${(basePrice * qty).toFixed(2)}`;
    root.querySelector("p:nth-of-type(1) span")
        .textContent = item.sechdule_start_date;
    root.querySelectorAll("p.font-normal span.ml-3")[0]
        .textContent = `$${itemTotal.toFixed(2)}`;
    root.querySelectorAll("p.font-normal span.ml-3")[1]
        .textContent = `+ $${(addonTotal + deliveryFee).toFixed(2)}`;

    // options list
    const ul = root.querySelector("ul");
    ul.innerHTML = "";
    (item.addons || []).forEach(a => {
      ul.insertAdjacentHTML("beforeend", `<li>${a.name}</li>`);
    });
    (item.delivery_pickup || []).forEach(d => {
      ul.insertAdjacentHTML("beforeend", `<li>${d.name}</li>`);
    });

    itemsEl.appendChild(clone);
  });

  // Compute grand total
  const grandTotal = +(subtotal + totalTax).toFixed(2);

  // 4) Inject into your summary block
  const spans = totalsBlock.querySelectorAll("div.flex.justify-between span.font-semibold");
  spans[0].textContent = `$${subtotal.toFixed(2)}`;
  spans[1].textContent = `$${totalTax.toFixed(2)}`;
  totalsBlock.querySelector("div.text-lg span:last-child")
      .textContent = `$${grandTotal.toFixed(2)}`;
});
</script>
@endpush

