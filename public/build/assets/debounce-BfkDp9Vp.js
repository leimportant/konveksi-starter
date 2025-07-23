function n(e,u){let t=null;return function(...i){t&&clearTimeout(t),t=setTimeout(()=>{e.apply(this,i)},u)}}export{n as d};
