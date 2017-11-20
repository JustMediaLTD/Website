/* $Id: dialog.jss,v 1.18 2004/08/16 16:44:00 engine Exp engine $  */
/* Copyright (c) Ohad Aloni 1990-2004. All rights reserved. */
/* Released under http://ohad.dyndns.org/license.txt (BSD) */
/************************************************************/
#include "alert.h"
/************************************************************/

function
msdbIeHideDialog(id)
{
	ALERT1("msdbIeHideDialog");
	jstr = id + ".style.visibility = 'hidden';" ;
	eval(jstr);
	id.isShown = 0;
}

/************************************************************/

/*
 * clip is the scrollable clip region
 * sTop and sLeft, are the clip position in the overall area
 * mx my are mouse cooordinates relative to the clip region
 * I don't know how to get the size of the whole area (yet)
 * but it doesn't matter
 * when setting the position it is set relative to the whole region,
 * not the clip ( i.e. setting to mouseXY will only position
 * in the natural place when not scrolled)
 */

function
msdbShowDialogAt(id, isTopLeft)
{
	ALERT1("msdbIeShowDialog: " + id);
	ALERT2("msdbIeShowDialog");

	jstr = "dw = " + id + ".offsetWidth ;" ;
	eval(jstr);
	jstr = "dh = " + id + ".offsetHeight ;" ;
	eval(jstr);

	sx = msdbScrollLeft();
	sy = msdbScrollTop();
	sw = document.body.clientWidth;
	sh = document.body.clientHeight;

	ax =  sx + msdbTop.mouseX;
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
		jstr = id + ".style.top = " + pos.y + " - 2 - "  + dh + ";" ;
		eval(jstr);
	}

	jstr = id + ".style.visibility = 'visible';" ;
	eval(jstr);

	msdbSetDialogVisibility(id, 1);
}

/************************************************************/

function
msdbShowDialog(id)
{
		msdbShowDialogAt(id, true);
}

/************************************************************/
/************************************************************/

function
msdbDiaVis(id, v)
{
	ALERT1("msdbDiaVis");
	this.id = id;
	this.v = v;
	return(this);
}

/******************************/

function
msdbSetDialogVisibility(id, v)
{
	ALERT1("msdbSetDialogVisibility");
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

/******************************/


function
msdbDialogVisibility(id)
{
	ALERT1("msdbDialogVisibility");
	if ( msdbTop.dialogVis == msdbTop.undef )
		msdbTop.dialogVis = new Array();
	
	v = msdbTop.dialogVis;
	for(i=0;i<v.length;i++)
		if( v[i].id == id )
			return(v[i].v) ;
	return(0);
}

/************************************************************/

function
msdbHideDialog(id)
{
	ALERT1("msdbHideDialog");
	msdbIeHideDialog(id);

	msdbSetDialogVisibility(id, 0);
}

/************************************************************/
/*
 * agent independant arith for positioning a dialog given:
 *
 * ax, ay - the absolute mouse click corrdinates
 * sx, sy - scroll region offset
 * sw, sh - scroll region size
 * dw, dh - the width and hight of the dialog
 * return.x, return.y - the resulting canvas relative position
 */

function
msdbPositionDialog(ax, ay, sx, sy, sw, sh, dw, dh)
{
	ALERT1("msdbPositionDialog");
	this.x = this.y = 0;

	sRight = sx + sw;
	sBottom = sy + sh ;

	/*	msdbAlert('msdbPositionDialog',	*/
		/*	'ax', ax,	*/
		/*	'ay', ay,	*/
		/*	'sx', sx,	*/
		/*	'sy', sy,	*/
		/*	'sw', sw,	*/
		/*	'sh', sh,	*/
		/*	'dw', dw,	*/
		/*	'dh', dh	*/
		/*	);	*/

	/*
	 * mx my are mouse cooordinates relative to the clip region
	 */
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

/************************************************************/

function
msdbPlaceDialog(idname, l, t)
{
	ALERT1("msdbPlaceDialog");
	ALERT2("msdbPlaceDialog");

	ids = msdbIdStyleName(idname);

	jstr = ids + ".left = " + l + " ; " ;
	eval(jstr);
	jstr = ids + ".top = " + t + " ; " ;
	eval(jstr);
}

/************************************************************/

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

/************************************************************/

function
msdbShowSelect(fname)
{
	var idname;

	idname = "msdbSpotID" + fname ;
	msdbToggleSelect(idname);
}

/************************************************************/

function msdbScrollLeft()
{
	return(document.documentElement.scrollLeft ?
				document.documentElement.scrollLeft :
				document.body.scrollLeft);
}

/******************************/

function msdbScrollTop()
{
	return(document.documentElement.scrollTop ?
				document.documentElement.scrollTop :
				document.body.scrollTop);
}

/************************************************************/
