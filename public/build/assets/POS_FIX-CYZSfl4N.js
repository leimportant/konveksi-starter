import{d as ae,r,c as z,f as o,g as i,p as m,u as p,Z as oe,x as y,k as t,C as D,l as P,m as I,F as v,B as C,j as a,D as x,y as h,U as ie,b as N,n as re,_ as le}from"./app-CJ7UkBbN.js";import{_ as de}from"./AppLayout.vue_vue_type_script_setup_true_lang-Bsw52OWo.js";import{u as ce}from"./useToast-u_qnC3Ro.js";import{T as K}from"./trash-2-__6xUXMm.js";import"./index-CcHQQhFt.js";import"./AppLogoIcon.vue_vue_type_script_setup_true_lang-Dmg2k5om.js";const ue={class:"p-2 md:p-4 space-y-8 bg-gray-50 min-h-screen"},me={class:"grid grid-cols-1 lg:grid-cols-3 gap-6"},pe={class:"lg:col-span-2"},fe={class:"mb-4 flex items-center justify-between"},ge={class:"grid grid-cols-4 sm:grid-cols-6 xl:grid-cols-4 gap-2"},ye=["onClick"],he=["src"],xe={key:1,class:"mb-2 w-full h-24 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs"},be={class:"font-semibold text-xs text-gray-900 truncate"},ve={class:"text-xs text-gray-400"},_e={class:"mt-1"},we={key:0},ke={class:"text-xs text-gray-400 line-through block"},Pe={key:0},Ce={class:"text-green-500 text-xs"},Se={key:1,class:"text-sm font-semibold text-gray-700"},Te={class:"bg-white rounded-1xl shadow-md p-4 sticky top-6 h-fit border border-gray-100 max-h-[90vh] overflow-auto"},je={class:"flex justify-between items-center mb-5"},qe={key:0,class:"text-center text-gray-400 py-8"},ze={class:"flex items-center gap-2 text-sm text-gray-600 mt-1"},De={class:"min-w-0"},Ie={class:"text-xs mr-1 truncate block max-w-[120px]"},Ne={key:0},Oe={class:"text-xs text-gray-400 line-through mr-1 truncate block max-w-[120px]"},Fe={class:"text-gray-700 font-semibold truncate block max-w-[120px]"},Ee={key:1,class:"text-gray-700 font-semibold truncate block max-w-[100px] text-xs"},Me=["onUpdate:modelValue","onChange"],Ve={class:"ml-auto text-right text-gray-400 text-xs"},Ae={class:"mt-6"},Be=["value"],$e={class:"mt-6 pt-4 border-t"},Le={class:"flex justify-between font-semibold text-sm text-gray-700"},Ue={class:"ml-auto text-right text-gray-700 font-medium"},Ke={key:0},He={key:1},Qe={key:0,class:"fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4"},Re={class:"bg-white rounded-lg shadow-lg max-w-sm w-full p-6"},We={class:"flex justify-between mb-4"},Ge={class:"font-semibold"},Xe={class:"flex justify-between mb-4"},Ye={class:"font-semibold text-green-600"},Je={class:"flex justify-end space-x-3"},Ze={key:1,class:"fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 p-4"},et={class:"w-full text-sm border-collapse border border-gray-300"},tt={class:"border border-gray-300 p-2"},nt={class:"border border-gray-300 p-2 text-right"},st={class:"border border-gray-300 p-2 text-right"},at={class:"border border-gray-300 p-2 text-right"},ot={class:"border border-gray-300 p-2 font-semibold text-right"},it={class:"mt-4"},rt={class:"mt-6 flex justify-end space-x-3"},lt=ae({__name:"POS_FIX",setup(dt){const d=ce(),S=r([]),u=r([]),T=r([]),g=r(null),_=r({placingOrder:!1}),j=r(""),O=r(1),H=r(1),q=r(!1),F=r([]),E=r(0),M=r(""),V=r(""),A=r(""),w=r(null),B=r(null),f=r(null),Q=z(()=>f.value===null?0:f.value-b.value>0?f.value-b.value:0),k=r(!1),R=s=>s?s.startsWith("storage/")?"/"+s:s.startsWith("/storage/")?s:"/storage/"+s:"",c=s=>typeof s!="number"||isNaN(s)?"0,00":new Intl.NumberFormat("id-ID",{style:"currency",currency:"IDR",minimumFractionDigits:2}).format(s);async function $(){try{const s=await N.get(`/api/stock?page=${O.value}&search=${j.value}`);S.value=s.data.data,H.value=s.data.last_page}catch(s){console.error("Error fetching products:",s),d.error("Failed to fetch products")}}async function W(){try{const s=await N.get("/api/payment-methods");T.value=s.data.data}catch{d.error("Failed to fetch payment methods")}}function G(){O.value=1,$()}const X=s=>{const e=u.value.find(n=>n.id===s.product_id);e?e.quantity++:u.value.push({id:s.product_id||s.id,product_name:s.product_name,uom_id:s.uom_id,qty_stock:s.qty_stock,image_path:s.image_path,price:s.price,discount:s.discount,price_sell:s.price_sell,quantity:1}),re(()=>{w.value&&(w.value.scrollTop=w.value.scrollHeight)})};function Y(s){u.value=u.value.filter(e=>e.id!==s)}function J(s){var n;s.quantity<1&&(s.quantity=1);const e=((n=S.value.find(l=>l.id===s.id))==null?void 0:n.qty_stock)??0;s.quantity>e&&(d.error("Stock limit reached"),s.quantity=e)}function Z(){if(u.value.length===0){d.error("Please add items to the cart");return}if(!g.value){d.error("Please select a payment method");return}f.value=null,k.value=!0}async function ee(){if(f.value===null||f.value<b.value){d.error("Paid amount must be at least total amount");return}k.value=!1,await te()}function L(){u.value=[],g.value=null}const b=z(()=>u.value.reduce((s,e)=>s+e.price*e.quantity,0)),U=z(()=>`${c(b.value)}`);async function te(){var s;if(u.value.length===0){d.error("Please add items to the cart");return}if(!g.value){d.error("Please select a payment method");return}_.value.placingOrder=!0;try{const e={items:u.value.map(l=>({product_id:l.product_id||l.id,product_name:l.product_name,quantity:l.quantity,price:l.price,discount:l.discount||0,price_sell:l.price_sell||l.price_sell})),payment_method_id:g.value,total_amount:b.value,paid_amount:f.value},n=await N.post("/api/pos/orders",e);F.value=n.data.items,E.value=n.data.total_amount,M.value=((s=T.value.find(l=>l.id===g.value))==null?void 0:s.name)||"",V.value=new Date(n.data.created_at).toLocaleString(),A.value=n.data.transaction_number||"",q.value=!0}catch(e){console.error("Error placing order:",e),d.error("Failed to place order")}finally{_.value.placingOrder=!1}}function ne(){q.value=!1,L(),g.value=null}function se(){var n;const s=(n=B.value)==null?void 0:n.innerHTML;if(!s){typeof d<"u"&&d.error?d.error("Print content not found."):console.error("Print content not found.");return}const e=window.open("","_blank","width=230,height=600");if(!e){typeof d<"u"&&d.error?d.error("Failed to open print window."):console.error("Failed to open print window.");return}e.document.write(`
    <html>
      <head>
        <title>Print Kasir</title>
        <style>
          /* Basic reset and page setup for printing */
          body {
            margin: 0;
            font-family: 'Courier New', Courier, monospace; /* Classic receipt font */
            font-size: 10px; /* Base font size, adjust as needed for 58mm width */
            line-height: 1.3; /* Spacing between lines */
            background-color: #fff; /* Ensure print background is white */
            color: #000; /* Ensure text is black */
          }

          /* The main container for the receipt content */
          .print-area {
            width: 58mm; /* Strict width for the thermal paper */
            padding: 2mm; /* Small padding around the content */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
          }

          /* Utility classes for text alignment */
          .text-center { text-align: center; }
          .text-left { text-align: left; }
          .text-right { text-align: right; }
          .font-bold { font-weight: bold; }

          /* Header section styling */
          .receipt-header {
            text-align: center;
            margin-bottom: 2mm;
          }
          .receipt-header .shop-icon { /* If you use an icon, e.g., an emoji or SVG */
            font-size: 22px; /* Adjust if you have a real icon/SVG */
            margin-bottom: 1mm;
          }
          .receipt-header .shop-name {
            font-weight: bold;
            font-size: 12px; /* Slightly larger for shop name */
            margin: 1mm 0;
          }
          .receipt-header .shop-address,
          .receipt-header .shop-phone,
          .receipt-header .transaction-id {
            font-size: 9px;
            margin: 0.5mm 0;
            line-height: 1.2; /* Tighter line height for address lines */
          }

          /* Dotted line separator */
          .dotted-line {
            border-top: 1px dashed #333; /* Dashed line for separation */
            margin: 1.5mm 0;
            height: 0;
            overflow: hidden;
          }

          /* Transaction details (date, time, cashier, etc.) */
          .transaction-info {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin: 1.5mm 0;
          }
          .transaction-info .left-column,
          .transaction-info .right-column {
            flex-basis: 48%; /* Distribute space */
          }
          .transaction-info .right-column {
            text-align: right;
          }
          .transaction-info p {
            margin: 0.5mm 0;
            line-height: 1.2;
          }

          /* Styling for the list of items */
          .items-section .item {
            margin-bottom: 1mm; /* Space between items */
          }
          .items-section .item-line-1 { /* Contains item name and total price for that item */
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.2mm; /* Minimal space before quantity/unit price line */
          }
          .items-section .item-name {
            text-align: left;
            word-break: break-word; /* Prevent overflow for long item names */
            padding-right: 5px; /* Space before price */
          }
          .items-section .item-total-price {
            text-align: right;
            white-space: nowrap; /* Keep price on one line */
            min-width: 50px; /* Ensure alignment, adjust as needed */
          }
          .items-section .item-line-2 { /* Contains quantity and price per unit */
            font-size: 9px;
            padding-left: 10px; /* Indent this line slightly, as seen in the image */
            text-align: left;
          }

          /* Summary section (Total QTY, Sub Total, Total, Bayar, Kembali) */
          .summary-section .summary-line {
            display: flex;
            justify-content: space-between;
            margin: 1mm 0;
            font-size: 10px;
          }
          .summary-section .summary-line.bold {
            font-weight: bold;
          }
           .summary-section .summary-line span:first-child { /* Label like "Total QTY :" */
            text-align: left;
            padding-right: 5px;
          }
          .summary-section .summary-line span:last-child { /* Value like "14" or "Rp 70.000" */
            text-align: right;
            white-space: nowrap;
            min-width: 60px; /* Ensure alignment, adjust as needed */
          }


          /* Footer message */
          .footer-message {
            text-align: center;
            margin-top: 2mm;
            font-size: 10px;
          }
          .footer-message p {
            margin: 0.5mm 0;
          }

          /* Print-specific styles: ensure only .print-area is visible and properly styled */
          @media print {
            body {
              font-size: 10px; /* Explicitly set for printing if different from screen */
            }
            body * {
              visibility: hidden;
            }
            .print-area, .print-area * {
              visibility: visible;
            }
            .print-area {
              position: absolute;
              left: 0;
              top: 0;
              background: white !important; /* Ensure white background for printing */
              box-shadow: none !important; /* No shadow when printing */
              color: #000 !important; /* Ensure black text */
              /* width, font-family, padding are inherited or already set on .print-area */
            }
            /* Hide elements not meant for printing, if any, by giving them a specific class */
            .no-print, .no-print * {
              display: none !important;
            }
          }
        </style>
      </head>
      <body>
        <div class="print-area">
          ${s}
        </div>
        <script>
          window.onload = function() {
            // A slight delay can sometimes help ensure all content and styles are rendered,
            // especially if there are images or complex CSS.
            setTimeout(function() {
              window.print();
              window.onafterprint = function() {
                window.close();
              };
            }, 100); // 100ms delay, adjust if needed or remove if not necessary
          };
        <\/script>
      </body>
    </html>
  `),e.document.close()}return $(),W(),(s,e)=>(i(),o(v,null,[m(p(oe),{title:"Point of Sale"}),m(de,null,{default:y(()=>[t("div",ue,[t("div",me,[t("div",pe,[t("div",fe,[e[4]||(e[4]=t("h2",{class:"text-xl font-semibold text-gray-800"},"Available Products",-1)),P(t("input",{type:"text","onUpdate:modelValue":e[0]||(e[0]=n=>j.value=n),onInput:G,placeholder:"Search products...",class:"border rounded px-3 py-1 text-sm w-48"},null,544),[[I,j.value]])]),t("div",ge,[(i(!0),o(v,null,C(S.value,n=>(i(),o("div",{key:n.id,class:"bg-white rounded-2xl p-4 shadow hover:shadow-md transition cursor-pointer border border-gray-200",onClick:l=>X(n)},[n.image_path?(i(),o("img",{key:0,src:R(n.image_path),alt:"product image",class:"mb-2 w-full h-24 object-cover rounded-lg"},null,8,he)):(i(),o("div",xe," No Image ")),t("p",be,a(n.product_name),1),t("p",ve,"Stock: "+a(n.qty_stock)+" / "+a(n.uom_id),1),t("div",_e,[n.discount&&n.discount>0?(i(),o("p",we,[t("span",ke,a(c(n.price)),1),n.price_sell!==void 0?(i(),o("span",Pe,a(c(n.price_sell)),1)):D("",!0),t("span",Ce," (Disc "+a(c(n.discount??0))+") ",1)])):(i(),o("p",Se,a(c(n.price)),1))])],8,ye))),128))])]),t("div",Te,[t("div",je,[e[6]||(e[6]=t("h2",{class:"text-lg font-semibold text-gray-800"},"Order Summary",-1)),m(p(x),{variant:"outline",size:"sm",onClick:L},{default:y(()=>[m(p(K),{class:"h-4 w-4 mr-1"}),e[5]||(e[5]=h(" Clear "))]),_:1,__:[5]})]),u.value.length===0?(i(),o("div",qe,e[7]||(e[7]=[t("p",null,"No items in cart",-1)]))):(i(),o("div",{key:1,class:"space-y-8 max-h-[500px] overflow-y-auto pr-1",ref_key:"orderList",ref:w},[(i(!0),o(v,null,C(u.value,n=>(i(),o("div",{key:n.id,class:"flex items-center justify-between gap-5 border-b pb-2"},[m(p(x),{variant:"ghost",size:"icon",class:"hover:bg-gray-100",onClick:l=>Y(n.id)},{default:y(()=>[m(p(K),{class:"h-4 w-4"})]),_:2},1032,["onClick"]),t("div",ze,[t("div",De,[t("span",Ie,a((n.product_id,n.product_name)),1),n.discount&&n.discount>0?(i(),o("span",Ne,[t("span",Oe,a(c(n.price)),1),t("span",Fe,a(c(n.price_sell||n.price-n.discount)),1)])):(i(),o("span",Ee,a(c(n.price)),1))]),e[8]||(e[8]=t("span",{class:"text-gray-400"},"x",-1)),P(t("input",{type:"number",min:"1",class:"w-10 border rounded px-2 py-1 text-xs","onUpdate:modelValue":l=>n.quantity=l,onChange:l=>J(n)},null,40,Me),[[I,n.quantity,void 0,{number:!0}]]),t("span",Ve,a(c(n.quantity*(n.price_sell||n.price))),1)])]))),128))],512)),t("div",Ae,[e[10]||(e[10]=t("label",{class:"text-sm font-medium text-gray-700"},"Payment Method",-1)),P(t("select",{"onUpdate:modelValue":e[1]||(e[1]=n=>g.value=n),class:"w-full mt-2 border rounded-lg p-2 text-sm"},[e[9]||(e[9]=t("option",{value:""},"Select payment method",-1)),(i(!0),o(v,null,C(T.value,n=>(i(),o("option",{key:n.id,value:n.id},a(n.name),9,Be))),128))],512),[[ie,g.value]])]),t("div",$e,[t("div",Le,[e[11]||(e[11]=t("span",null,"Total",-1)),t("span",Ue,a(U.value),1)]),m(p(x),{class:"w-full mt-4 text-sm font-medium",onClick:Z,disabled:_.value.placingOrder},{default:y(()=>[_.value.placingOrder?(i(),o("span",Ke,"Processing...")):(i(),o("span",He,"Bayar"))]),_:1},8,["disabled"])])])]),k.value?(i(),o("div",Qe,[t("div",Re,[e[16]||(e[16]=t("h3",{class:"text-lg font-semibold mb-4"},"Masukkan Jumlah Bayar",-1)),P(t("input",{type:"number",min:"0","onUpdate:modelValue":e[2]||(e[2]=n=>f.value=n),class:"w-full border rounded px-3 py-2 mb-4 text-lg",placeholder:"Masukkan jumlah bayar"},null,512),[[I,f.value,void 0,{number:!0}]]),t("div",We,[e[12]||(e[12]=t("div",null,"Total:",-1)),t("div",Ge,a(U.value),1)]),t("div",Xe,[e[13]||(e[13]=t("div",null,"Kembalian:",-1)),t("div",Ye,a(c(Q.value)),1)]),t("div",Je,[m(p(x),{variant:"outline",onClick:e[3]||(e[3]=n=>k.value=!1)},{default:y(()=>e[14]||(e[14]=[h("Batal")])),_:1,__:[14]}),m(p(x),{onClick:ee},{default:y(()=>e[15]||(e[15]=[h("Konfirmasi")])),_:1,__:[15]})])])])):D("",!0),q.value?(i(),o("div",Ze,[t("div",{class:"bg-white rounded-lg shadow-lg max-w-md w-full p-6 overflow-auto max-h-[80vh] print-area",ref_key:"printArea",ref:B},[e[24]||(e[24]=t("h3",{class:"text-lg font-bold mb-4 text-center"},"Print Preview",-1)),t("div",null,[t("table",et,[e[18]||(e[18]=t("thead",null,[t("tr",null,[t("th",{class:"border border-gray-300 p-2 text-left"},"Product"),t("th",{class:"border border-gray-300 p-2 text-right"},"Qty"),t("th",{class:"border border-gray-300 p-2 text-right"},"Price"),t("th",{class:"border border-gray-300 p-2 text-right"},"Subtotal")])],-1)),t("tbody",null,[(i(!0),o(v,null,C(F.value,n=>(i(),o("tr",{key:n.product_id},[t("td",tt,a(n.product_name),1),t("td",nt,a(n.quantity),1),t("td",st,a(c(n.price)),1),t("td",at,a(c(n.price*n.quantity)),1)]))),128)),t("tr",null,[e[17]||(e[17]=t("td",{colspan:"3",class:"border border-gray-300 p-2 font-semibold text-right"},"Total",-1)),t("td",ot,a(c(E.value)),1)])])]),t("div",it,[t("p",null,[e[19]||(e[19]=t("strong",null,"Payment Method:",-1)),h(" "+a(M.value),1)]),t("p",null,[e[20]||(e[20]=t("strong",null,"Date:",-1)),h(" "+a(V.value),1)]),t("p",null,[e[21]||(e[21]=t("strong",null,"Transaction Number:",-1)),h(" "+a(A.value),1)])])]),t("div",rt,[m(p(x),{variant:"outline",onClick:ne},{default:y(()=>e[22]||(e[22]=[h("CLOSE")])),_:1,__:[22]}),m(p(x),{onClick:se},{default:y(()=>e[23]||(e[23]=[h("CETAK")])),_:1,__:[23]})])],512)])):D("",!0)])]),_:1})],64))}}),yt=le(lt,[["__scopeId","data-v-355c9684"]]);export{yt as default};
