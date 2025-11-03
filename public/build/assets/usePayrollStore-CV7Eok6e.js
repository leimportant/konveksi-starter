import{R as f,r as u,c as h,b as p}from"./app-BjS9V559.js";const L=f("payroll",()=>{const r=u([]),i=u(""),s=u(""),_=u(""),l=u([]),c=u(!1),v=h(()=>({totalGaji:r.value.reduce((a,o)=>a+o.total_gaji,0),totalPotongan:r.value.reduce((a,o)=>a+o.potongan,0),totalMeal:r.value.reduce((a,o)=>a+o.uang_makan,0)})),y=async()=>{if(!c.value&&(c.value=!0,!(!i.value||!s.value)))try{const a=await p.get("/api/payroll",{params:{start:i.value,end:s.value,search:_.value}});r.value=a.data.data,l.value=[]}catch(a){console.error("Failed to load payroll:",a)}finally{c.value=!1}},d=async()=>{if(!c.value&&(c.value=!0,!(!i.value||!s.value)))try{const a=await p.get("/api/payroll/preview",{params:{start:i.value,end:s.value,search:_.value}});r.value=a.data.data,l.value=[]}catch(a){console.error("Failed to load payroll:",a)}finally{c.value=!1}};return{employees:r,startDate:i,endDate:s,selectedEmployees:l,totalSummary:v,loadData:y,loadPayrollData:d,updatePotongan:(a,o)=>{const e=r.value.find(t=>t.employee_id===a);e&&(e.potongan=o,e.total_gaji=e.total_upah+e.uang_makan+e.lembur-e.potongan)},toggleSelect:a=>{const o=l.value.indexOf(a);o===-1?l.value.push(a):l.value.splice(o,1)},closeSingle:async a=>{try{const o=a.details.map(n=>({size_id:n.size_id,variant:n.variant,qty:n.qty,model_desc:n.model_desc,created_at:n.created_at}));await p.post("/api/payroll/close",{period_start:i.value,period_end:s.value,employees:[{employee_id:a.employee_id,total_gaji:a.total_gaji,uang_makan:a.uang_makan,lembur:a.lembur??0,potongan:a.potongan??0,details:o}]}),a.status="closed";const e=`
*Slip Gaji Periode*
${i.value} s/d ${s.value}

*Nama:* ${a.employee_name}
*ID:* ${a.employee_id}

*Upah Pokok:* Rp ${a.total_upah.toLocaleString()}
*Uang Makan:* Rp ${a.uang_makan.toLocaleString()}
*Lembur:* Rp ${(a.lembur??0).toLocaleString()}
*Potongan:* Rp ${(a.potongan??0).toLocaleString()}

*Total Diterima:* Rp ${a.total_gaji.toLocaleString()}

Terima kasih ✅
`,t=(a.phone_number??"08976640804").replace(/[^0-9]/g,"").replace(/^0/,"62");if(t){const n=`https://wa.me/${t}?text=${encodeURIComponent(e)}`;window.open(n,"_blank")}l.value=l.value.filter(n=>n!==a.employee_id)}catch(o){console.error(o),alert("Gagal closing payroll")}d()},closePayroll:async()=>{if(l.value.length!==0)try{const a={period_start:i.value,period_end:s.value,employees:r.value.filter(e=>l.value.includes(e.employee_id)).map(e=>({employee_id:e.employee_id,total_gaji:e.total_gaji,uang_makan:e.uang_makan,lembur:e.lembur??0,potongan:e.potongan??0,details:e.details.map(t=>({size_id:t.size_id,variant:t.variant,qty:t.qty,model_desc:t.model_desc,created_at:t.created_at}))}))},{data:o}=await p.post("/api/payroll/close",a);return r.value.forEach(e=>{l.value.includes(e.employee_id)&&(e.status="closed")}),a.employees.forEach(e=>{const t=r.value.find(g=>g.employee_id===e.employee_id);if(!t)return;const n=`
*Slip Gaji Periode*
${i.value} s/d ${s.value}

*Nama:* ${t.employee_name}
*ID:* ${t.employee_id}

*Upah Pokok:* Rp ${t.total_upah.toLocaleString()}
*Uang Makan:* Rp ${t.uang_makan.toLocaleString()}
*Lembur:* Rp ${(t.lembur??0).toLocaleString()}
*Potongan:* Rp ${(t.potongan??0).toLocaleString()}

*Total Diterima:* Rp ${t.total_gaji.toLocaleString()}

Terima kasih ✅
      `,m=(t.phone_number??"").replace(/[^0-9]/g,"").replace(/^0/,"62");if(m.length>=10){const g=`https://wa.me/${m}?text=${encodeURIComponent(n)}`;window.open(g,"_blank")}}),l.value=[],await d(),o}catch(a){console.error(a),alert("Gagal closing payroll")}}}});export{L as u};
