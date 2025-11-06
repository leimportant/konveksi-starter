import{R as $,r as c,c as S,b as p}from"./app-DTsOkX4v.js";const j=$("payroll",()=>{const r=c([]),i=c(""),s=c(""),v=c(""),m=c(""),l=c([]),u=c(!1),y=c(null),h=S(()=>({totalGaji:r.value.reduce((a,t)=>a+t.total_gaji,0),totalPotongan:r.value.reduce((a,t)=>a+t.potongan,0),totalMeal:r.value.reduce((a,t)=>a+t.uang_makan,0)})),f=async()=>{if(!u.value&&(u.value=!0,!(!i.value||!s.value)))try{const a=await p.get("/api/payroll",{params:{start:i.value,end:s.value,search:v.value}});r.value=a.data.data,l.value=[]}catch(a){console.error("Failed to load payroll:",a)}finally{u.value=!1}},d=async()=>{if(!u.value&&(u.value=!0,!(!i.value||!s.value)))try{const a=await p.get("/api/payroll/preview",{params:{start:i.value,end:s.value,search:m.value}});r.value=a.data.data,l.value=[]}catch(a){console.error("Failed to load payroll:",a)}finally{u.value=!1}};return{employees:r,startDate:i,endDate:s,selectedEmployees:l,totalSummary:h,loadData:f,loadPayrollData:d,updatePotongan:(a,t)=>{const e=r.value.find(o=>o.employee_id===a);e&&(e.potongan=t,e.total_gaji=e.total_upah+e.uang_makan+e.lembur-e.potongan)},toggleSelect:a=>{const t=l.value.indexOf(a);t===-1?l.value.push(a):l.value.splice(t,1)},closeSingle:async a=>{try{const t=a.details.map(n=>({size_id:n.size_id,activity_role_id:n.activity_role_id,variant:n.variant,qty:n.qty,model_desc:n.model_desc,created_at:n.created_at}));await p.post("/api/payroll/close",{period_start:i.value,period_end:s.value,employees:[{employee_id:a.employee_id,activity_role_id:a.activity_role_id,total_gaji:a.total_gaji,uang_makan:a.uang_makan,lembur:a.lembur??0,potongan:a.potongan??0,details:t}]}),a.status="closed";const e=`
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
`,o=(a.phone_number??"08976640804").replace(/[^0-9]/g,"").replace(/^0/,"62");if(o){const n=`https://wa.me/${o}?text=${encodeURIComponent(e)}`;window.open(n,"_blank")}l.value=l.value.filter(n=>n!==a.employee_id)}catch(t){console.error(t),alert("Gagal closing payroll")}d()},closePayroll:async()=>{if(l.value.length!==0)try{const a={period_start:i.value,period_end:s.value,employees:r.value.filter(e=>l.value.includes(e.employee_id)).map(e=>({employee_id:e.employee_id,activity_role_id:e.activity_role_id,total_gaji:e.total_gaji,uang_makan:e.uang_makan,lembur:e.lembur??0,potongan:e.potongan??0,details:e.details.map(o=>({size_id:o.size_id,variant:o.variant,activity_role_id:o.activity_role_id,qty:o.qty,model_desc:o.model_desc,created_at:o.created_at}))}))},{data:t}=await p.post("/api/payroll/close",a);return r.value.forEach(e=>{l.value.includes(e.employee_id)&&(e.status="closed")}),a.employees.forEach(e=>{const o=r.value.find(_=>_.employee_id===e.employee_id);if(!o)return;const n=`
*Slip Gaji Periode*
${i.value} s/d ${s.value}

*Nama:* ${o.employee_name}
*ID:* ${o.employee_id}

*Upah Pokok:* Rp ${o.total_upah.toLocaleString()}
*Uang Makan:* Rp ${o.uang_makan.toLocaleString()}
*Lembur:* Rp ${(o.lembur??0).toLocaleString()}
*Potongan:* Rp ${(o.potongan??0).toLocaleString()}

*Total Diterima:* Rp ${o.total_gaji.toLocaleString()}

Terima kasih ✅
      `,g=(o.phone_number??"").replace(/[^0-9]/g,"").replace(/^0/,"62");if(g.length>=10){const _=`https://wa.me/${g}?text=${encodeURIComponent(n)}`;window.open(_,"_blank")}}),l.value=[],await d(),t}catch(a){console.error(a),alert("Gagal closing payroll")}},fetchPayslip:async a=>{try{const{data:t}=await p.get(`/api/payroll/slip/${a}`);return y.value=t.data,t.data}catch(t){console.error(t)}},currentSlip:y}});export{j as u};
