function
msdbIeHideDialog(id)
{
        ;
        jstr = id + ".style.visibility = 'hidden';" ;
        eval(jstr);
        id.isShown = 0;
}
function
msdbShowDialogAt(id, isTopLeft)
{
        ;
        ;

        jstr = "dw = " + id + ".offsetWidth ;" ;
        eval(jstr);
        jstr = "dh = " + id + ".offsetHeight ;" ;
        eval(jstr);

        sx = msdbScrollLeft();
        sy = msdbScrollTop();
        sw = document.body.clientWidth;
        sh = document.body.clientHeight;

        ax = sx + msdbTop.mouseX;
        ay = sy + msdbTop.mouseY;

        pos = new msdbPositionDialog(ax, ay, sx, sy, sw, sh, dw, dh);

        if ( isTopLeft ) {
                jstr = id + ".style.left = " + pos.x + ";" ;
                eval(jstr);
                jstr = id + ".style.top = " + pos.y + ";" ;
                eval(jstr);
        } else {
                jstr = id + ".style.left = " + pos.x + " - 2 - " + dw + ";" ;
                eval(jstr);
                jstr = id + ".style.top = " + pos.y + " - 2 - " + dh + ";" ;
                eval(jstr);
        }

        jstr = id + ".style.visibility = 'visible';" ;
        eval(jstr);

        msdbSetDialogVisibility(id, 1);
}



function
msdbShowDialog(id)
{
                msdbShowDialogAt(id, true);
}




function
msdbDiaVis(id, v)
{
        ;
        this.id = id;
        this.v = v;
        return(this);
}



function
msdbSetDialogVisibility(id, v)
{
        ;
        if ( msdbTop.dialogVis == msdbTop.undef )
                msdbTop.dialogVis = new Array();

        va = msdbTop.dialogVis;
        for(i=0;i<va.length;i++)
                if( va[i].id == id ) {
                        va[i].v = v ;
                        return;
                }
        va[i] = new msdbDiaVis(id, v);
}




function
msdbDialogVisibility(id)
{
        ;
        if ( msdbTop.dialogVis == msdbTop.undef )
                msdbTop.dialogVis = new Array();

        v = msdbTop.dialogVis;
        for(i=0;i<v.length;i++)
                if( v[i].id == id )
                        return(v[i].v) ;
        return(0);
}



function
msdbHideDialog(id)
{
        ;
        msdbIeHideDialog(id);

        msdbSetDialogVisibility(id, 0);
}
function
msdbPositionDialog(ax, ay, sx, sy, sw, sh, dw, dh)
{
        ;
        this.x = this.y = 0;

        sRight = sx + sw;
        sBottom = sy + sh ;
        mx = ax - sx;
        my = ay - sy;

        nativeX = ax;
        nativeY = ay;

        this.x = nativeX;
        if ( (this.x + dw) > sx + sw )
                this.x = sx + sw - dw;
        if ( this.x < sx )
                this.x = sx;

        this.y = nativeY;
        if ( (this.y + dh) > sy + sh )
                this.y = sy + sh - dh;
        if ( this.y < sy )
                this.y = sy;

        return(this);
}



function
msdbPlaceDialog(idname, l, t)
{
        ;
        ;

        ids = msdbIdStyleName(idname);

        jstr = ids + ".left = " + l + " ; " ;
        eval(jstr);
        jstr = ids + ".top = " + t + " ; " ;
        eval(jstr);
}



function
msdbToggleSelect(idname)
{
        vis = msdbDialogVisibility(idname);
        if ( vis == 1 ) {
                msdbHideDialog(idname);
                return;
        }

        msdbShowDialog(idname);

        msdbPlaceDialog(idname,
                msdbTop.mouseX + msdbScrollLeft() - 8,
                msdbTop.mouseY + msdbScrollTop() + 4
                );
}



function
msdbShowSelect(fname)
{
        var idname;

        idname = "msdbSpotID" + fname ;
        msdbToggleSelect(idname);
}



function msdbScrollLeft()
{
        return(document.documentElement.scrollLeft ?
                                document.documentElement.scrollLeft :
                                document.body.scrollLeft);
}



function msdbScrollTop()
{
        return(document.documentElement.scrollTop ?
                                document.documentElement.scrollTop :
                                document.body.scrollTop);
}
