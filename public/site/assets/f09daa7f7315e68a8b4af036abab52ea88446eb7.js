/* De Gouden Bol — interactie. Vervangt GSAP (±110 KB) door ±4 KB; zelfde bewegingen en timing. */
(function(){
  var d=document,root=d.documentElement,reduce=matchMedia('(prefers-reduced-motion:reduce)').matches;
  var $=function(s,c){return (c||d).querySelector(s)},$$=function(s,c){return [].slice.call((c||d).querySelectorAll(s))};

  /* Voortgangsbalk + nav (gebundeld via rAF) */
  var sb=$('#sBar'),nav=$('#mainNav'),tick=false;
  function onScroll(){
    if(tick)return;tick=true;
    requestAnimationFrame(function(){
      var max=root.scrollHeight-innerHeight,s=max>0?scrollY/max:0;
      if(sb)sb.style.transform='scaleX('+s+')';
      if(nav)nav.classList.toggle('scrolled',scrollY>60);
      tick=false;
    });
  }
  addEventListener('scroll',onScroll,{passive:true});onScroll();

  /* Eigen cursor (alleen muis, geen reduced-motion; loop stopt zodra de ring rust) */
  var dot=$('#cDot'),ring=$('#cRing');
  if(dot&&ring&&root.classList.contains('cursor-on')){
    var mx=0,my=0,rx=0,ry=0,raf=0;
    var loop=function(){
      rx+=(mx-rx)*.11;ry+=(my-ry)*.11;
      ring.style.transform='translate('+(rx-18)+'px,'+(ry-18)+'px)';
      raf=(Math.abs(mx-rx)>.1||Math.abs(my-ry)>.1)?requestAnimationFrame(loop):0;
    };
    d.addEventListener('mousemove',function(e){
      mx=e.clientX;my=e.clientY;
      dot.style.transform='translate('+(mx-4)+'px,'+(my-4)+'px)';
      if(!raf)raf=requestAnimationFrame(loop);
    },{passive:true});
  }

  /* Mobiel menu */
  var tg=$('.nav-toggle'),menu=$('#nav-menu');
  if(tg&&menu){
    tg.addEventListener('click',function(){
      var o=menu.classList.toggle('open');tg.setAttribute('aria-expanded',o?'true':'false');
    });
    d.addEventListener('keydown',function(e){
      if(e.key==='Escape'&&menu.classList.contains('open')){menu.classList.remove('open');tg.setAttribute('aria-expanded','false');tg.focus();}
    });
    $$('a',menu).forEach(function(a){a.addEventListener('click',function(){menu.classList.remove('open');tg.setAttribute('aria-expanded','false');});});
  }

  /* Reveal-animaties (zelfde afstanden, duur en easing als de GSAP-versie) */
  function observe(sel,margin,fn){
    var els=$$(sel);if(!els.length)return;
    if(!('IntersectionObserver' in window)){els.forEach(fn);return;}
    var io=new IntersectionObserver(function(es){
      es.forEach(function(e){if(e.isIntersecting){fn(e.target);io.unobserve(e.target);}});
    },{rootMargin:'0px 0px -'+margin+'% 0px'});
    els.forEach(function(el){io.observe(el);});
  }
  observe('.rev',11,function(el){el.classList.add('in');});
  observe('.rev-l',13,function(el){el.classList.add('in');});
  observe('.rev-r',13,function(el){el.classList.add('in');});
  observe('.stagger-grid',18,function(g){
    [].forEach.call(g.children,function(c,i){c.style.setProperty('--i',i);c.classList.add('in');});
  });

  /* Tellers */
  $$('.sc').forEach(function(el){el.dataset.final=el.textContent;});
  observe('.sc',14,function(el){
    var t=parseInt(el.dataset.to,10);
    if(reduce){el.textContent=t;return;}
    var s=performance.now(),dur=1800;
    (function step(n){
      var p=Math.min((n-s)/dur,1),e=1-(1-p)*(1-p);
      el.textContent=Math.floor(t*e);
      if(p<1)requestAnimationFrame(step);else el.textContent=t;
    })(s);
  });
  if(!reduce)$$('.sc').forEach(function(el){el.textContent='0';});

  /* Loader + hero-intro */
  var loader=$('#loader');
  function startHero(){root.classList.add('hero-go');try{sessionStorage.setItem('gb','1');}catch(e){}}
  if(loader&&!root.classList.contains('no-loader')){
    var lBar=$('#lBar'),lPct=$('#lPct'),lWm=$('#lWm'),lSb=$('#lSb'),pct=0;
    setTimeout(function(){if(lWm){lWm.style.transition='opacity .6s';lWm.style.opacity='1';}},200);
    setTimeout(function(){if(lSb){lSb.style.transition='opacity .5s';lSb.style.opacity='1';}},400);
    var iv=setInterval(function(){
      pct+=Math.random()*13+3;
      if(pct>=100){
        pct=100;clearInterval(iv);
        if(lBar)lBar.style.width='100%';if(lPct)lPct.textContent='100%';
        setTimeout(function(){loader.style.transition='opacity .6s';loader.style.opacity='0';
          setTimeout(function(){loader.style.display='none';startHero();},640);},400);
      }
      if(lBar)lBar.style.width=Math.floor(pct)+'%';if(lPct)lPct.textContent=Math.floor(pct)+'%';
    },80);
  }else if(root.classList.contains('js')){startHero();}

  /* Sticky Top 3 */
  var sf=$('#sf');
  if(sf){
    var pill=$('.sf-pill',sf),close=$('.sf-close',sf),open=true;
    var set=function(o){open=o;sf.classList.toggle('closed',!o);pill.setAttribute('aria-expanded',o?'true':'false');};
    if(innerWidth<768)set(false);
    pill.addEventListener('click',function(){set(true);});
    close.addEventListener('click',function(){set(false);pill.focus();});
    setTimeout(function(){sf.classList.add('show');},(loader&&!root.classList.contains('no-loader'))?2600:1200);
    var aan=$('#aanmelden');
    if(aan&&'IntersectionObserver' in window){
      new IntersectionObserver(function(es){sf.classList.toggle('hide',es[0].isIntersecting);},{rootMargin:'0px 0px -35% 0px'}).observe(aan);
    }
  }

  /* Scorebalken */
  var bars=$$('.score-bar-fill');
  if(bars.length&&!reduce){
    bars.forEach(function(b){b.style.width='0%';});
    var bio='IntersectionObserver' in window?new IntersectionObserver(function(es){
      es.forEach(function(e){if(e.isIntersecting){e.target.style.width=e.target.dataset.w;bio.unobserve(e.target);}});
    },{threshold:.3}):null;
    bars.forEach(function(b){if(bio)bio.observe(b);else b.style.width=b.dataset.w;});
  }

  /* Openingstijden: vandaag markeren en open/gesloten bepalen (Nederlandse tijd) */
  var ot=$('.ot-list[data-hours]');
  if(ot){
    var hours=JSON.parse(ot.dataset.hours),keys=['zo','ma','di','wo','do','vr','za'];
    var p=new Intl.DateTimeFormat('en-GB',{timeZone:'Europe/Amsterdam',weekday:'short',hour:'2-digit',minute:'2-digit',month:'numeric',day:'numeric',hour12:false}).formatToParts(new Date()),g={};
    p.forEach(function(x){g[x.type]=x.value;});
    var wd={Sun:0,Mon:1,Tue:2,Wed:3,Thu:4,Fri:5,Sat:6}[g.weekday],today=keys[wd],mo=+g.month,dy=+g.day;
    var mins=(+g.hour%24)*60+(+g.minute),inSeason=mo>=11||mo===1||(mo===2&&dy<=1);
    $$('.ot-row',ot).forEach(function(r){if(r.dataset.day===today)r.classList.add('today');});
    var isOpen=false;
    if(inSeason&&hours[today]){var h=hours[today].split('-'),a=h[0].split(':'),b=h[1].split(':');isOpen=mins>=(+a[0]*60+ +a[1])&&mins<(+b[0]*60+ +b[1]);}
    d.body.setAttribute('data-open',isOpen?'yes':'no');
    if(!inSeason)$$('.status-closed').forEach(function(s){s.lastChild.textContent='Buiten seizoen';});
  }

  /* Delen en bewaren op het profiel */
  var sh=$('[data-share]');
  if(sh)sh.addEventListener('click',function(){
    var data={title:d.title,url:location.href};
    if(navigator.share){navigator.share(data).catch(function(){});}
    else if(navigator.clipboard){navigator.clipboard.writeText(location.href).then(function(){sh.setAttribute('aria-label','Link gekopieerd');setTimeout(function(){sh.setAttribute('aria-label','Deel dit profiel');},2000);});}
  });
  var sv=$('[data-save]');
  if(sv){
    var key='gb-save:'+location.pathname;
    try{if(localStorage.getItem(key))sv.setAttribute('aria-pressed','true');}catch(e){}
    sv.addEventListener('click',function(){
      var on=sv.getAttribute('aria-pressed')!=='true';sv.setAttribute('aria-pressed',on?'true':'false');
      try{on?localStorage.setItem(key,'1'):localStorage.removeItem(key);}catch(e){}
    });
  }

  /* Filter en zoeken op het bakkersoverzicht */
  var grid=$('#bkGrid');
  if(grid){
    var items=$$('li',grid),prov='alle',q='',count=$('#resultCount'),none=$('#noResults');
    var apply=function(){
      var n=0;
      items.forEach(function(li){
        var ok=(prov==='alle'||li.dataset.prov===prov)&&(!q||li.dataset.name.indexOf(q)>-1||li.dataset.city.indexOf(q)>-1);
        li.hidden=!ok;if(ok)n++;
      });
      count.textContent=n;none.style.display=n?'none':'block';
    };
    $$('.filter-pill').forEach(function(b){
      b.addEventListener('click',function(){
        $$('.filter-pill').forEach(function(x){x.classList.remove('active');x.setAttribute('aria-pressed','false');});
        b.classList.add('active');b.setAttribute('aria-pressed','true');prov=b.dataset.prov;apply();
      });
    });
    var si=$('#searchInput');
    if(si)si.addEventListener('input',function(){q=si.value.toLowerCase().trim();apply();});
  }


  /* Veelgestelde vragen (één tegelijk open, zoals in het origineel) */
  $$('.faq-q').forEach(function(btn){
    btn.addEventListener('click',function(){
      var item=btn.closest('.faq-item')||btn.parentElement,wasOpen=item.classList.contains('open');
      $$('.faq-item').forEach(function(i){
        i.classList.remove('open');$('.faq-q',i).setAttribute('aria-expanded','false');$('.faq-a',i).style.maxHeight='0px';
      });
      if(!wasOpen){item.classList.add('open');btn.setAttribute('aria-expanded','true');var a=$('.faq-a',item);a.style.maxHeight=a.scrollHeight+'px';}
    });
  });


  /* Interactieve kaart van Nederland */
  var nlm=$('[data-nlm]');
  if(nlm){
    var topG=$('.nlm-top',nlm),useEl=$('.nlm-top use',nlm),tip=$('.nlm-tip',nlm),infoEl=$('.nlm-info',nlm),defInfo=infoEl.innerHTML,cur=null,marks={};
    $$('.nlm-mark',nlm).forEach(function(m){marks[m.getAttribute('data-for')]=m;});
    var touch=matchMedia('(hover:none), (pointer:coarse)').matches,tapPrev=null;
    if(touch)d.addEventListener('touchstart',function(e){if(!nlm.contains(e.target))hide();},{passive:true});
    var show=function(a){
      if(cur===a)return;hide();cur=a;
      useEl.setAttribute('href','#'+a.id);topG.classList.add('on');
      var m=marks[a.getAttribute('data-slug')];if(m)m.classList.add('is-on');
      infoEl.innerHTML='<b>'+a.getAttribute('data-name')+'</b> &middot; '+a.getAttribute('data-info');
      tip.innerHTML='<b>'+a.getAttribute('data-name')+'</b><span>'+a.getAttribute('data-info')+'</span>';
    };
    var hide=function(){
      if(!cur)return;
      topG.classList.remove('on');var m=marks[cur.getAttribute('data-slug')];if(m)m.classList.remove('is-on');
      cur=null;infoEl.innerHTML=defInfo;tip.classList.remove('show');
    };
    var place=function(x,y){
      var r=nlm.getBoundingClientRect(),w=tip.offsetWidth,h=tip.offsetHeight;
      var lx=Math.min(Math.max(x-r.left+16,0),r.width-w),ly=Math.max(y-r.top-h-14,0);
      tip.style.left=lx+'px';tip.style.top=ly+'px';tip.classList.add('show');
    };
    $$('.nlm-p',nlm).forEach(function(a){
      a.addEventListener('mouseenter',function(){show(a);});
      a.addEventListener('mousemove',function(e){place(e.clientX,e.clientY);});
      a.addEventListener('mouseleave',function(){if(!touch)hide();});
      a.addEventListener('focus',function(){show(a);var b=a.getBoundingClientRect();place(b.left+b.width/2,b.top+b.height/3);});
      a.addEventListener('blur',function(){if(!touch)hide();});
      a.addEventListener('touchstart',function(){tapPrev=cur;},{passive:true});
      a.addEventListener('click',function(e){
        if(touch&&tapPrev!==a){e.preventDefault();show(a);var b=a.getBoundingClientRect();place(b.left+b.width/2,b.top+b.height/3);}
        tapPrev=null;
      });
    });
    $$('.nlm-ring',nlm).forEach(function(r,i){r.style.setProperty('--k',i);});
    if('IntersectionObserver' in window){
      var nio=new IntersectionObserver(function(es){if(es[0].isIntersecting){nlm.classList.add('is-in');nio.disconnect();}},{rootMargin:'0px 0px -15% 0px'});nio.observe(nlm);
    }else{nlm.classList.add('is-in');}
  }


  /* Provinciekaarten: spotlight en kanteling (alleen muis, geen reduced-motion) */
  var cards=$$('.pcard-a');
  if(cards.length&&matchMedia('(hover:hover) and (pointer:fine)').matches&&!reduce){
    cards.forEach(function(c){
      c.addEventListener('pointermove',function(e){
        var r=c.getBoundingClientRect(),px=(e.clientX-r.left)/r.width,py=(e.clientY-r.top)/r.height;
        c.style.setProperty('--mx',(px*100)+'%');c.style.setProperty('--my',(py*100)+'%');
        c.style.setProperty('--ry',((px-.5)*10)+'deg');c.style.setProperty('--rx',((.5-py)*8)+'deg');
      });
      c.addEventListener('pointerleave',function(){c.style.setProperty('--rx','0deg');c.style.setProperty('--ry','0deg');});
    });
  }


  /* De test als scrollverhaal */
  var story=$('[data-story]');
  if(story){
    var steps=$$('.story-step',story),dots=$$('.story-dot',story),ons=$$('[data-in]',story),chap=$('#stage-chap'),bar=$('#stage-bar'),n=steps.length,cur=0,listT=0,zeroT=0,flipT=0;
    var lbl=$('#s-dlabel'),defLbl=lbl?lbl.textContent:'',rows=$$('.s-row',story),order=[0,1,2,3,4],zero=$('#s-zero');
    var layout=function(){
      order.forEach(function(ri,rank){var r=rows[ri];r.style.translate='0 '+(8+rank*64)+'px';$('.s-rk',r).textContent=rank+1;
        $('.s-st',r).textContent=rank===0?'Voorlopige Top 10':(rank===1?'Gestegen':(rank<5?'Gedaald':''));});
    };
    var startList=function(){
      if(reduce||listT)return;
      listT=setInterval(function(){var m=order.pop();order.splice(1,0,m);layout();},2600);
    };
    var stopList=function(){clearInterval(listT);listT=0;};
    var runZero=function(){
      if(!zero)return;clearInterval(zeroT);
      if(reduce){zero.textContent='0';return;}
      var v=12;zero.textContent=v;
      setTimeout(function(){zeroT=setInterval(function(){v--;zero.textContent=v;if(v<=0)clearInterval(zeroT);},210);},1100);
    };
    var setStep=function(i){
      if(i===cur)return;cur=i;
      story.setAttribute('data-step',i);
      ons.forEach(function(el){el.classList.toggle('on',(','+el.getAttribute('data-in')+',').indexOf(','+i+',')>-1);});
      steps.forEach(function(s,k){s.classList.toggle('is-active',k===i-1);});
      dots.forEach(function(d,k){if(k===i-1)d.setAttribute('aria-current','step');else d.removeAttribute('aria-current');});
      if(chap)chap.textContent='Hoofdstuk '+i+' van '+n;if(bar)bar.style.width=(i/n*100)+'%';
      $$('.s-arc',story).forEach(function(a){a.setAttribute('tabindex',i===6?'0':'-1');});
      story.classList.remove('flipped');clearTimeout(flipT);
      if(i!==3){story.classList.remove('force-name');$$('[data-anon]',story).forEach(function(b){b.setAttribute('aria-pressed',b.getAttribute('data-anon')==='1'?'true':'false');});}
      if(i===8)flipT=setTimeout(function(){story.classList.add('flipped');},1600);
      if(i===9){layout();startList();}else stopList();
      if(i===10)runZero();
    };
    layout();setStep(1);
    if('IntersectionObserver' in window){
      var sio=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting)setStep(+e.target.getAttribute('data-step'));});},{rootMargin:innerWidth<=900?'-58% 0px -32% 0px':'-50% 0px -50% 0px'});
      steps.forEach(function(s){sio.observe(s);});
    }
    $$('[data-anon]',story).forEach(function(b){b.addEventListener('click',function(){
      var anon=b.getAttribute('data-anon')==='1';story.classList.toggle('force-name',!anon);
      $$('[data-anon]',story).forEach(function(x){x.setAttribute('aria-pressed',x===b?'true':'false');});
    });});
    var rp=$('[data-replay]',story);
    if(rp)rp.addEventListener('click',function(){story.classList.remove('flipped');clearTimeout(flipT);flipT=setTimeout(function(){story.classList.add('flipped');},1300);});
    $$('.s-arc',story).forEach(function(a){
      var on=function(){if(lbl)lbl.textContent=a.getAttribute('data-label');},off=function(){if(lbl)lbl.textContent=defLbl;};
      a.addEventListener('mouseenter',on);a.addEventListener('focus',on);a.addEventListener('mouseleave',off);a.addEventListener('blur',off);
    });
  }


  /* Diepte: parallax, achtergrondfoto per hoofdstuk, scrollbeweging */
  var frame=$('.stage-frame');
  if(frame&&story){
    var layers=$$('.bgl',frame),bgs=(frame.getAttribute('data-bgs')||'').split('|'),act=0;
    var setBg=function(i){
      var u=bgs[(i-1)%bgs.length];if(!u||!layers.length)return;
      var next=layers[act^1];next.style.backgroundImage='url("'+u+'")';
      layers[act].classList.remove('on');next.classList.add('on');act^=1;
    };
    var origSet=setStep;
    setStep=function(i){var changed=i!==cur;origSet(i);if(changed)setBg(i);};
    setBg(1);
    if(!reduce&&matchMedia('(hover:hover) and (pointer:fine)').matches){
      frame.addEventListener('pointermove',function(e){
        var r=frame.getBoundingClientRect();
        frame.style.setProperty('--px',(((e.clientX-r.left)/r.width-.5)*2).toFixed(3));
        frame.style.setProperty('--py',(((e.clientY-r.top)/r.height-.5)*2).toFixed(3));
      });
      frame.addEventListener('pointerleave',function(){frame.style.setProperty('--px',0);frame.style.setProperty('--py',0);});
    }
    if(!reduce){
      var st=false;
      addEventListener('scroll',function(){
        if(st)return;st=true;requestAnimationFrame(function(){
          var r=story.getBoundingClientRect(),t=Math.min(Math.max(-r.top/Math.max(r.height-innerHeight,1),0),1);
          frame.style.setProperty('--sp',t.toFixed(3));st=false;
        });
      },{passive:true});
    }
  }

  /* Simulator: beoordeel zelf */
  var sim=$('[data-sim]');
  if(sim){
    var inputs=$$('input[type=range]',sim),gradeEl=$('#sim-grade'),ptsEl=$('#sim-pts'),labEl=$('#sim-label'),pubEl=$('#sim-pub'),ring=$('.sim-ring',sim);
    var bands=[[90,'Uitzonderlijk'],[80,'Zeer goed'],[70,'Goed'],[60,'Voldoende tot ruim voldoende'],[50,'Publiceerbaar volgens de minimumgrens'],[0,'Onder 50%']];
    var calc=function(){
      var t=0;inputs.forEach(function(r,i){var v=+r.value,m=+r.getAttribute('data-max');t+=v;$('#so-'+i).textContent=v+' / '+m;r.style.setProperty('--p',(v/m*100)+'%');});
      gradeEl.textContent=(t/10).toFixed(1).replace('.',',');ptsEl.textContent=t+' van 100 punten';
      ring.style.strokeDashoffset=(465*(1-t/100)).toFixed(1);
      for(var k=0;k<bands.length;k++){if(t>=bands[k][0]){labEl.textContent=bands[k][1];break;}}
      pubEl.textContent=t>=50?'Kan openbaar op de Voorlijst (5,0 of hoger).':'Niet openbaar. Wel vertrouwelijke terugkoppeling.';
    };
    inputs.forEach(function(r){r.addEventListener('input',calc);});
    var rs=$('[data-sim-reset]');
    if(rs)rs.addEventListener('click',function(){inputs.forEach(function(r){r.value=Math.round(+r.getAttribute('data-max')*.8);});calc();});
    calc();
  }


  (function(){
  /* Achtergrondvideo's: pas laden en afspelen als ze in beeld zijn */
  var vids=$$('.bgv video, video[data-lazy]');
  if(vids.length&&!reduce&&innerWidth>700&&!(navigator.connection&&navigator.connection.saveData)){
    var src=function(v){
      var webm=v.canPlayType&&v.canPlayType('video/webm; codecs="vp9"');
      return (webm?v.getAttribute('data-webm'):v.getAttribute('data-mp4'))||v.getAttribute('data-mp4');
    };
    var vio=new IntersectionObserver(function(es){
      es.forEach(function(e){
        var v=e.target;
        if(e.isIntersecting){
          if(!v.getAttribute('src')){v.setAttribute('src',src(v));v.addEventListener('playing',function(){v.classList.add('on');},{once:true});}
          var pr=v.play();if(pr&&pr.catch)pr.catch(function(){});
        }else{v.pause();}
      });
    },{rootMargin:'160px'});
    vids.forEach(function(v){vio.observe(v);});
  }

  /* Ambient stof: gouden stofdeeltjes op meerdere dieptes (alleen zonder reduced-motion) */
  if(!reduce&&!d.getElementById('ambient')){
    var cv=d.createElement('canvas');cv.id='ambient';cv.setAttribute('aria-hidden','true');d.body.appendChild(cv);
    var cx=cv.getContext('2d'),dpr=Math.min(devicePixelRatio||1,1.5),W=0,Hh=0,parts=[],mx=0,my=0,sy=0,run=true;
    var sprite=function(r,a){var c=d.createElement('canvas'),s=Math.ceil(r*2*dpr)+2;c.width=c.height=s;var g=c.getContext('2d'),gr=g.createRadialGradient(s/2,s/2,0,s/2,s/2,s/2);
      gr.addColorStop(0,'rgba(232,176,60,'+a+')');gr.addColorStop(.5,'rgba(220,160,40,'+(a*.45)+')');gr.addColorStop(1,'rgba(220,160,40,0)');g.fillStyle=gr;g.fillRect(0,0,s,s);return c;};
    var sprites=[sprite(3,.55),sprite(6,.4),sprite(14,.2),sprite(34,.1)];
    var resize=function(){W=innerWidth;Hh=innerHeight;cv.width=W*dpr;cv.height=Hh*dpr;};
    resize();addEventListener('resize',resize);
    var count=innerWidth<700?14:34;
    for(var i=0;i<count;i++){var k=Math.floor(Math.random()*4);parts.push({x:Math.random(),y:Math.random(),k:k,z:[.35,.6,1,1.6][k],v:.012+Math.random()*.03,w:Math.random()*6.28});}
    addEventListener('scroll',function(){sy=scrollY;},{passive:true});
    d.addEventListener('mousemove',function(e){mx=(e.clientX/W-.5);my=(e.clientY/Hh-.5);},{passive:true});
    d.addEventListener('visibilitychange',function(){run=!d.hidden;if(run)requestAnimationFrame(frame);});
    var last=0;
    var frame=function(t){
      if(!run)return;
      var dt=Math.min((t-last)/1000||0,.05);last=t;
      cx.clearRect(0,0,cv.width,cv.height);
      parts.forEach(function(p){
        p.y-=p.v*dt*p.z;p.w+=dt*.6;if(p.y<-.1){p.y=1.1;p.x=Math.random();}
        var px=(p.x+Math.sin(p.w)*.012+mx*.02*p.z)*W,py=((p.y*Hh-(sy*.08*p.z))%(Hh*1.2)+Hh*1.2)%(Hh*1.2)-Hh*.1+my*14*p.z;
        var s=sprites[p.k],sz=s.width/dpr;cx.drawImage(s,(px-sz/2)*dpr,(py-sz/2)*dpr,s.width,s.height);
      });
      requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
  }
  })();


  /* Pauzeknop voor de video in de verhaalsectie */
  var vt=$('[data-vid-toggle]');
  if(vt){
    var rv=$('video[data-lazy]',vt.parentNode);
    vt.addEventListener('click',function(){
      if(!rv.getAttribute('src')){rv.setAttribute('src',(rv.canPlayType&&rv.canPlayType('video/webm; codecs="vp9"'))?rv.getAttribute('data-webm'):rv.getAttribute('data-mp4'));}
      var paused=rv.paused;
      if(paused){var pr=rv.play();if(pr&&pr.catch)pr.catch(function(){});vt.textContent='Pauzeer video';vt.setAttribute('aria-pressed','false');}
      else{rv.pause();vt.textContent='Speel video af';vt.setAttribute('aria-pressed','true');}
    });
    if(reduce||innerWidth<=700){vt.textContent='Speel video af';vt.setAttribute('aria-pressed','true');}
  }


  /* Uitlegfilm: Bolle gaat door de test (eigen bereik) */
  (function(){
    var ex=$('[data-ex]');if(!ex)return;
    var TXT=[],dots=$$('.ex-dot',ex),title=$('#ex-title',ex),text=$('#ex-text',ex),cap=$('.ex-cap',ex),prog=$('.ex-bar2 i',ex),
        playBtn=$('[data-ex-play]',ex),pts=$('#ex-pts',ex),stat=$('#ex-stat',ex),N=6,STEP_MS=6200;
    $$('.ex-plain li',ex).forEach(function(li){var b=li.querySelector('b').textContent.replace(/\.$/,'');TXT.push([b,li.textContent.slice(li.querySelector('b').textContent.length).trim()]);});
    var cur=0,elapsed=0,playing=!reduce,visible=false,timers=[],raf=0;
    var clear=function(){timers.forEach(clearTimeout);timers=[];cancelAnimationFrame(raf);};
    var go=function(i,manual){
      i=((i-1+N)%N)+1;clear();cur=i;elapsed=0;
      ex.setAttribute('data-step',i);ex.setAttribute('data-sub','0');
      dots.forEach(function(b,k){if(k===i-1)b.setAttribute('aria-current','step');else b.removeAttribute('aria-current');});
      title.textContent=TXT[i-1][0];text.textContent=TXT[i-1][1];
      cap.setAttribute('aria-live',(!playing||manual)?'polite':'off');
      if(i===4){
        var wr=$('#exWipeR',ex),wa=$('#exWipeA',ex);
        if(reduce){if(wr)wr.setAttribute('width','724');}
        else if(wa&&wa.beginElementAt){wa.endElement&&wa.endElement();if(wr)wr.setAttribute('width','0');wa.beginElementAt(.45);}
        else if(wr){wr.setAttribute('width','724');}
      }
      if(i===4&&pts){
        if(reduce){pts.textContent='87';}
        else{pts.textContent='0';var t0=performance.now();var tick=function(n){var p=Math.min((n-t0-300)/1500,1);pts.textContent=Math.round(87*Math.max(p,0));if(p<1)raf=requestAnimationFrame(tick);};raf=requestAnimationFrame(tick);}
      }
      if(i===5){
        var rk=$('#ex-rank',ex);
        if(stat)stat.textContent='Nieuw';if(rk)rk.textContent='5';
        var steps=reduce?[0,0,0]:[1100,2200,3300];
        steps.forEach(function(ms,k){timers.push(setTimeout(function(){ex.setAttribute('data-sub',String(k+1));if(stat)stat.textContent='Gestegen \u25B2';if(rk)rk.textContent=String(4-k);},ms));});
        if(reduce){ex.setAttribute('data-sub','3');if(stat)stat.textContent='Gestegen \u25B2';if(rk)rk.textContent='2';}
      }
    };
    var setPlay=function(on){
      playing=on;playBtn.textContent=on?'Pauzeer':'Speel af';playBtn.setAttribute('aria-pressed',on?'false':'true');
      cap.setAttribute('aria-live',on?'off':'polite');
    };
    var last=performance.now();
    var loop=function(now){
      var dt=now-last;last=now;
      if(playing&&visible&&!d.hidden){elapsed+=dt;if(elapsed>=STEP_MS)go(cur+1,false);}
      prog.style.width=Math.min(elapsed/STEP_MS*100,100)+'%';
      requestAnimationFrame(loop);
    };
    dots.forEach(function(b){b.addEventListener('click',function(){go(+b.getAttribute('data-go'),true);});});
    $('[data-ex-prev]',ex).addEventListener('click',function(){go(cur-1,true);});
    $('[data-ex-next]',ex).addEventListener('click',function(){go(cur+1,true);});
    playBtn.addEventListener('click',function(){setPlay(!playing);});
    ex.addEventListener('keydown',function(e){if(e.key==='ArrowRight'){go(cur+1,true);}else if(e.key==='ArrowLeft'){go(cur-1,true);}});
    if('IntersectionObserver' in window){new IntersectionObserver(function(es){visible=es[0].isIntersecting;},{threshold:.35}).observe(ex);}else{visible=true;}
    setPlay(playing);go(1,false);requestAnimationFrame(loop);
  })();


  /* Zwevende widget: nieuwe aanmeldingen en ranglijst (gegevensgestuurd, eigen bereik) */
  (function(){
    var w=d.getElementById('gbw');if(!w)return;
    if(d.querySelector('[data-story]')){w.style.display='none';return;}   /* niet over het scrollverhaal */
    var pill=$('.gbw-pill',w),panel=$('#gbw-panel',w),closeB=$('.gbw-close',w),pillT=$('#gbw-pill-t',w),upd=$('#gbw-upd',w),
        tabs=[$('#gbw-t1',w),$('#gbw-t2',w)],pans=[$('#gbw-p1',w),$('#gbw-p2',w)],base=w.getAttribute('data-base')||'',idx=w.getAttribute('data-idx')||'';
    var data={signups:[],rankings:{},provinces:[],start:'',capacity:600,total:0};
    try{data=JSON.parse(d.getElementById('gb-live').textContent)||data;}catch(e){}
    var START=data.start?new Date(data.start+'T00:00:00+01:00'):null,MON=['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
    var curProv=null,tabIx=0,isOpen=false,cdT=0,userChose=false,userTab=false;
    var el=function(t,c,x){var e=d.createElement(t);if(c)e.className=c;if(x!=null)e.textContent=x;return e;};
    var days=function(s){var a=s.split('-'),dt=new Date(+a[0],+a[1]-1,+a[2]),t=new Date();t.setHours(0,0,0,0);return Math.round((t-dt)/864e5);};
    var rel=function(s){if(!s)return '';var n=days(s);if(n<=0)return 'vandaag';if(n===1)return 'gisteren';if(n<14)return n+' dagen geleden';var a=s.split('-');return (+a[2])+' '+MON[+a[1]-1];};
    var fmt=function(x){return (Math.round(x*10)/10).toFixed(1).replace('.',',');};
    var link=function(path){return base+path+(idx&&path.slice(-1)==='/'?idx:'');};
    var left=function(){return START?START-new Date():0;};
    var started=function(){return START&&left()<=0;};

    var countdown=function(){
      var box=el('div','gbw-cd'),ms=Math.max(left(),0),v=[Math.floor(ms/864e5),Math.floor(ms%864e5/36e5),Math.floor(ms%36e5/6e4),Math.floor(ms%6e4/1e3)],n=['dagen','uur','min','sec'];
      v.forEach(function(x,i){var c=el('div');c.appendChild(el('b',null,('0'+x).slice(-2)));c.appendChild(el('span',null,n[i]));box.appendChild(c);});
      return box;
    };
    var renderSignups=function(){
      var p=pans[0];p.textContent='';
      var sum=el('p','gbw-sum');sum.appendChild(el('b',null,String(data.total||data.signups.length)));sum.appendChild(d.createTextNode(' bekendgemaakt · max. '+(data.capacity||600)+' plekken'));p.appendChild(sum);
      if(!data.signups.length){
        var em=el('div','gbw-empty');
        if(!started()){em.appendChild(el('b',null,'De eerste deelnemers volgen. '));em.appendChild(d.createTextNode('Ze worden bekendgemaakt voor de start van de beoordelingen op 1 november.'));p.appendChild(em);p.appendChild(countdown());p.appendChild(el('p','gbw-empty','Tot de start van de officiële beoordelingen.'));}
        else{em.appendChild(d.createTextNode('Er zijn nog geen nieuwe aanmeldingen bekendgemaakt.'));p.appendChild(em);}
        return;
      }
      var ul=el('ul','gbw-list');
      data.signups.slice(0,6).forEach(function(s){
        var li=el('li'),it=el('div','gbw-item'),tx=el('div','gbw-tx');
        it.appendChild(el('span','gbw-ic','\u2605'));tx.appendChild(el('b',null,s.name));tx.appendChild(el('span',null,[s.city,s.province].filter(Boolean).join(' \u00B7 ')+' \u00B7 '+rel(s.date)));it.appendChild(tx);
        if(s.date&&days(s.date)<=3)it.appendChild(el('span','gbw-bd new','Nieuw'));
        li.appendChild(it);ul.appendChild(li);
      });
      p.appendChild(ul);
    };
    var renderRank=function(){
      var p=pans[1];p.textContent='';
      var provs=data.provinces||[];if(!provs.length)return;
      var sel=el('select','gbw-sel');sel.setAttribute('aria-label','Kies een provincie');
      provs.forEach(function(x){var o=el('option',null,x.name);o.value=x.slug;if(x.slug===curProv)o.selected=true;sel.appendChild(o);});
      sel.addEventListener('change',function(){curProv=sel.value;userChose=true;try{sessionStorage.setItem('gbw-prov',curProv);}catch(e){}renderRank();});
      p.appendChild(sel);
      var rows=(data.rankings||{})[curProv]||[],name=(provs.filter(function(x){return x.slug===curProv;})[0]||{}).name||'';
      if(!rows.length){
        var em=el('div','gbw-empty');
        if(!started()){em.appendChild(el('b',null,'De Voorlijst van '+name+' opent op 1 november. '));em.appendChild(d.createTextNode('Vanaf dan verschijnen hier de eerste scores van 5,0 of hoger.'));}
        else em.appendChild(d.createTextNode('Er zijn nog geen gepubliceerde scores in '+name+'. Alleen scores van 5,0 of hoger worden openbaar.'));
        p.appendChild(em);
      }else{
        var ul=el('ul','gbw-list');
        rows.slice(0,5).forEach(function(r,i){
          var li=el('li'),it=el('div','gbw-item'),tx=el('div','gbw-tx');
          it.appendChild(el('span','gbw-rk',String(i+1)));tx.appendChild(el('b',null,r.name));tx.appendChild(el('span',null,[r.city,r.status].filter(Boolean).join(' \u00B7 ')));it.appendChild(tx);
          it.appendChild(el('span','gbw-bd'+(i<3?' top':''),fmt(r.score)));li.appendChild(it);ul.appendChild(li);
        });
        p.appendChild(ul);
      }
      var more=el('a','gbw-more','Bekijk de volledige Voorlijst van '+name+' \u2192');more.href=link('provincies/'+curProv+'/');p.appendChild(more);
    };
    var pillText=function(){
      var t='Aanmeldingen en ranglijst';
      if(START&&!started()){var dd=Math.ceil(left()/864e5);t=dd<=1?'Start morgen':'Nog '+dd+' dagen';}
      else{var any=false;for(var k in (data.rankings||{}))if((data.rankings[k]||[]).length)any=true;if(any)t='Top 3 per provincie';else if(data.signups.length)t=data.signups.length+' aanmeldingen';}
      pillT.textContent=t;
      var seen='';try{seen=localStorage.getItem('gbw-seen')||'';}catch(e){}
      var newest=data.signups.length?data.signups[0].date:'';
      w.classList.toggle('has-new',!!newest&&newest>seen&&days(newest)<=7);
    };
    var renderUpd=function(){
      if(!data.updated){upd.textContent='';return;}
      var dt=new Date(data.updated);if(isNaN(dt)){upd.textContent='';return;}
      try{upd.textContent='Bijgewerkt '+new Intl.DateTimeFormat('nl-NL',{timeZone:'Europe/Amsterdam',day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'}).format(dt);}catch(e){}
    };
    var renderAll=function(){renderSignups();renderRank();pillText();renderUpd();};
    var pickProv=function(){
      var s='';try{s=sessionStorage.getItem('gbw-prov')||'';}catch(e){}
      var provs=data.provinces||[],ok=function(x){return provs.some(function(p){return p.slug===x;});};
      if(s&&ok(s))return s;
      for(var k in (data.rankings||{}))if((data.rankings[k]||[]).length&&ok(k))return k;
      return provs.length?provs[0].slug:null;
    };
    var setTab=function(i,focus){
      tabIx=i;tabs.forEach(function(t,k){t.setAttribute('aria-selected',k===i?'true':'false');t.tabIndex=k===i?0:-1;pans[k].hidden=k!==i;});
      if(focus)tabs[i].focus();
    };
    var tick=function(){clearInterval(cdT);cdT=0;if(isOpen&&tabIx===0&&!data.signups.length&&START&&!started()){cdT=setInterval(function(){if(d.hidden)return;var old=$('.gbw-cd',pans[0]);if(old)old.replaceWith(countdown());},1000);}};
    var open=function(on,byUser){
      isOpen=on;panel.hidden=!on;pill.setAttribute('aria-expanded',on?'true':'false');w.classList.toggle('open',on);
      try{sessionStorage.setItem('gbw-open',on?'1':'0');}catch(e){}
      if(on){try{if(data.signups.length)localStorage.setItem('gbw-seen',data.signups[0].date);}catch(e){}pillText();if(byUser)tabs[tabIx].focus();}
      else if(byUser)pill.focus();
      tick();
    };
    pill.addEventListener('click',function(){open(true,true);});
    closeB.addEventListener('click',function(){open(false,true);});
    d.addEventListener('keydown',function(e){if(e.key==='Escape'&&isOpen)open(false,true);});
    tabs.forEach(function(t,i){
      t.addEventListener('click',function(){userTab=true;setTab(i,false);tick();});
      t.addEventListener('keydown',function(e){if(e.key==='ArrowRight'||e.key==='ArrowLeft'){e.preventDefault();setTab((i+1)%2,true);tick();}});
    });
    var bestTab=function(){var any=Object.keys(data.rankings||{}).some(function(k){return (data.rankings[k]||[]).length;});return (!data.signups.length&&any)?1:0;};
    curProv=pickProv();renderAll();setTab(bestTab(),false);
    try{if(sessionStorage.getItem('gbw-open')==='1')open(true,false);}catch(e){}
    /* wegfaden bij de aanmeldblokken, zodat hij ze niet bedekt */
    if('IntersectionObserver' in window){
      var vis=[],targets=$$('.cta-dark,#aanmeld-form,.site-footer'),io=new IntersectionObserver(function(es){es.forEach(function(e){vis[targets.indexOf(e.target)]=e.isIntersecting;});w.classList.toggle('gbw-hide',vis.some(Boolean));},{rootMargin:'0px 0px -18% 0px'});
      targets.forEach(function(x){io.observe(x);});
    }
    /* live verversen: leest assets/live.json (alleen via http/https) */
    var url=w.getAttribute('data-live-url');
    var refresh=function(){
      if(d.hidden||!/^https?:/.test(location.protocol))return;
      fetch(url+'?t='+Date.now(),{cache:'no-store'}).then(function(r){return r.ok?r.json():null;}).then(function(j){
        if(!j||!j.provinces)return;data=j;if(!userChose)curProv=pickProv();renderAll();if(!userTab)setTab(bestTab(),false);tick();
      }).catch(function(){});
    };
    refresh();setInterval(refresh,90000);setInterval(pillText,60000);
  })();

  /* Aanmeldformulier */
  var form=$('#aanmeld-form');
  if(form){
    var status=$('#form-status');
    var rules={
      kraam:function(v){return v.trim()?'':'Vul de naam van je kraam in.';},
      contact:function(v){return v.trim()?'':'Vul de naam van de contactpersoon in.';},
      email:function(v){if(!v.trim())return 'Vul je e-mailadres in.';return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim())?'':'Controleer je e-mailadres, bijvoorbeeld naam@voorbeeld.nl.';},
      locatie:function(v){return v.trim()?'':'Vul de locatie van je kraam in.';},
      provincie:function(v){return v?'':'Kies de provincie van je kraam.';}
    };
    var check=function(n){
      var f=form.elements[n];if(!f||!rules[n])return true;
      var m=rules[n](f.value),w=f.closest('.f-field'),e=w.querySelector('.f-err');
      w.classList.toggle('invalid',!!m);f.setAttribute('aria-invalid',m?'true':'false');if(e)e.textContent=m;return !m;
    };
    /* Pas valideren bij verzenden; daarna live bijwerken zodra een veld als fout is gemarkeerd (geen verspringende layout tijdens klikken) */
    Object.keys(rules).forEach(function(n){var f=form.elements[n];if(!f)return;var fix=function(){if(f.closest('.f-field').classList.contains('invalid'))check(n);};f.addEventListener('input',fix);f.addEventListener('change',fix);});
    var show=function(k,t){status.className='f-status show '+k;status.textContent=t;status.focus();};
    form.addEventListener('submit',function(e){
      e.preventDefault();var bad=null;
      Object.keys(rules).forEach(function(n){if(!check(n)&&!bad)bad=form.elements[n];});
      if(bad){bad.focus();show('bad','Je aanmelding is nog niet verstuurd. Controleer de gemarkeerde velden.');return;}
      if(form.elements.website&&form.elements.website.value)return;
      var ep=form.getAttribute('data-endpoint');
      if(!ep){show('ok','Voorbeeldmodus: er is niets verstuurd, het formulier is nog niet gekoppeld aan een ontvanger.');return;}
      var btn=form.querySelector('button[type=submit]'),lbl=btn.textContent;btn.disabled=true;btn.textContent='Bezig met versturen';
      fetch(ep,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}})
        .then(function(r){if(!r.ok)throw 0;form.reset();show('ok','Bedankt, je aanmelding is binnen. Je ontvangt een bevestiging per e-mail.');})
        .catch(function(){show('bad','Versturen is niet gelukt. Probeer het zo opnieuw of mail naar '+(form.getAttribute('data-email')||'ons')+'.');})
        .then(function(){btn.disabled=false;btn.textContent=lbl;});
    });
  }
})();
