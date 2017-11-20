function
showmenuie5(menuid)
{
        alert("menu.jss" + ": " + 11 + ": " + 1 + ": " + "showmenuie5");;
        msdbSaveMouse();

        var rightedge=document.body.clientWidth-ht.mouseX ;
        var bottomedge=document.body.clientHeight-ht.mouseY ;

        if (rightedge<menuid.offsetWidth)
                menuid.style.left =
                        document.body.scrollLeft+ht.mouseX - menuid.offsetWidth ;
        else
                menuid.style.left=document.body.scrollLeft+ht.mouseX

        if (bottomedge<menuid.offsetHeight)
                menuid.style.top =
                        document.body.scrollTop+ht.mouseY-menuid.offsetHeight ;
        else
                menuid.style.top=document.body.scrollTop+ht.mouseY ;

        menuid.style.visibility="visible" ;
        return false ;
}



function
hidemenuie5(menuid)
{
        alert("menu.jss" + ": " + 38 + ": " + 1 + ": " + "hidemenuie5");;
        menuid.style.visibility="hidden"
}



function
highlightie5()
{
        alert("menu.jss" + ": " + 47 + ": " + 1 + ": " + "highlightie5");;
        if (event.srcElement.className=="iemenuitems") {
                event.srcElement.style.backgroundColor="highlight"
                event.srcElement.style.color="white"

        }
}



function
lowlightie5()
{
        alert("menu.jss" + ": " + 60 + ": " + 1 + ": " + "lowlightie5");;
        if (event.srcElement.className=="iemenuitems") {
                event.srcElement.style.backgroundColor=""
                event.srcElement.style.color="black"
                window.status=''
        }
}



function
jumptoie5()
{
        alert("menu.jss" + ": " + 73 + ": " + 1 + ": " + "jumptoie5");;
        if (event.srcElement.className=="iemenuitems")
                window.location=event.srcElement.url;
}
function
msdbShowIeMenu(menuid, arg1, arg2)
{
        alert("menu.jss" + ": " + 93 + ": " + 1 + ": " + "msdbShowIeMenu");;
        if ( ht.ieMenuIsV == 1 ) {
                msdbHideIeMenu();
                ht.ieMenuIsV = 0;
        }

        if ( ht.ieMenuIsV != 0 )
                return;

        ht.menuid = menuid;


        ht.menuid.arg1 = arg1;
        ht.menuid.arg2 = arg2;

        ht.ieMenuIsV = 2;

        showmenuie5(menuid);

        document.onclick=msdbHideIeMenu;
}



function
msdbHideIeMenu()
{
        alert("menu.jss" + ": " + 120 + ": " + 1 + ": " + "msdbHideIeMenu");;
        if ( ht.ieMenuIsV == 2 ) {
                ht.ieMenuIsV = 1;
                return;
        }
        if ( ht.ieMenuIsV == 0 )
                return;
        ht.ieMenuIsV = 0;
        hidemenuie5(ht.menuid);
}
