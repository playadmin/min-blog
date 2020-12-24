(function () {
    var S = 0, W = window, B = document.body, D = document.documentElement
    ,OVERLAY = D.querySelector('.nav-overlay');
    var initScroll = function () {
      var btn = D.querySelector('.fixed-box.col').children
      ,f = 60 ,t = 300 ,i = parseInt(t * f / 1000)
      ,tpx = 21 ,bpx = 81;
      var setScroll = function () {
        if (!S) {
          var s = B.scrollTop || D.scrollTop
          ,h = D.scrollHeight - D.clientHeight
          btn[0].style.display = s > tpx ? 'block' : 'none'
          btn[1].style.display = h - s > bpx ? 'block' : 'none'
        }
      }
      var top = function () {
        var s = D.scrollTop || B.scrollTop
        ,px = Math.ceil(s / i)
        ,timer = requestAnimationFrame(function fn () {
          s = D.scrollTop || B.scrollTop
          if (s > 0) {
            S || (S = 1)
            W.scrollTo(0, s - px)
            timer = W.requestAnimationFrame(fn);
          } else {
            S = 0
            W.cancelAnimationFrame(timer);
            setScroll()
          }
        })
      };
      var bottom = function () {
        var s = D.scrollTop || B.scrollTop
        ,h = D.scrollHeight - D.clientHeight
        ,b = h - s
        ,px = Math.ceil(b / i)
        ,timer = requestAnimationFrame(function fn () {
          var s = D.scrollTop || B.scrollTop
          if(s < h){
            S || (S = 1)
            W.scrollTo(0, s + px)
            timer = W.requestAnimationFrame(fn);
          }else{
            S = 0
            W.cancelAnimationFrame(timer);
            setScroll()
          }
        })
      };
      btn[0].onclick = top
      btn[1].onclick = bottom
      W.addEventListener('resize', setScroll)
      W.addEventListener('scroll', setScroll)
      setScroll()
    }
    
    // 顶部导航响应
    var initHnav = function () {
      var b = D.querySelector('.head-nav-more')
      ,nav = D.querySelector('.lay-head .nav')
      if (b && nav && nav.children) {
        b.onclick = function () {
          if (nav.clientWidth) {
            nav.style.display = ''
            OVERLAY.style.top = ''
          } else {
            nav.style.display = 'block'
            OVERLAY.style.top = 0
          }
        }
        W.addEventListener('resize', function () {
          if (nav.style.display) {
            nav.style.display = ''
            OVERLAY.style.top = ''
          }
        })
      }
    }

    // 处理文章图片
    var setImgs = function () {
      var overW = 640
      var insertImg = function (org, src) {
        var div = document.createElement('div')
        // ,op = B.style.position || ''
        ,of = B.style.overflow || ''
        div.style.position = 'fixed'
        div.style.overflow = 'auto'
        div.style.zIndex = 99
        div.style.display = 'flex'
        div.style.alignItems = 'center'
        div.style.justifyContent = 'center'
        div.style.top = 0
        div.style.left = 0
        div.style.right = 0
        div.style.bottom = 0
        div.innerHTML = '<img src="' + src + '" style="width: '+org.width+'px; height: '+org.height+'px;">'
        div.onclick = function () {
          B.removeChild(div)
          // B.style.position = op
          B.style.overflow = of
          OVERLAY.style.top = ''
          OVERLAY.style.zIndex = ''
        }
        OVERLAY.style.top = 0
        OVERLAY.style.zIndex = 98
        B.appendChild(div)
        // B.style.position = 'absolute'
        B.style.overflow = 'hidden'

      }
      var setLay = function (org, img) {
        if (org.width <= overW) return
        img.onclick = function () {
          insertImg(org, img.src)
        }
      }
      var get = function (I) {
        var img = new Image();
        img.src = I.src;
        if (img.complete) {
          setLay(img, I)
        } else {
          img.onload = function (e) {
            setLay(img, I)
          }
        }
      }
      var imgs = D.querySelectorAll('.art img')
      for (i = 0, len = imgs.length; i !== len; ++i) {
        get(imgs[i])
      }
    }
    // setImgs()
    setTimeout(function () {
      initHnav()
      initScroll()
      setImgs()
    });
  })()