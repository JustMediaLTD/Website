/* $Id: menu.jss,v 1.9 2004/01/13 12:15:55 engine Exp engine $  */
/* Copyright (c) Ohad Aloni 1990-2004. All rights reserved. */
/* Released under http://ohad.dyndns.org/license.txt (BSD) */
/************************************************************/
#include "alert.h"
/************************************************************/

function
showmenuie5(menuid)
{
	ALERT1("showmenuie5");
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

/************************************************************/

function
hidemenuie5(menuid)
{
	ALERT1("hidemenuie5");
	menuid.style.visibility="hidden"
}

/************************************************************/

function
highlightie5()
{
	ALERT1("highlightie5");
	if (event.srcElement.className=="iemenuitems") {
		event.srcElement.style.backgroundColor="highlight"
		event.srcElement.style.color="white"
		/*	window.status=event.srcElement.url;	*/
	}
}

/************************************************************/

function
lowlightie5()
{
	ALERT1("lowlightie5");
	if (event.srcElement.className=="iemenuitems") {
		event.srcElement.style.backgroundColor=""
		event.srcElement.style.color="black"
		window.status=''
	}
}

/************************************************************/

function
jumptoie5()
{
	ALERT1("jumptoie5");
	if (event.srcElement.className=="iemenuitems")
		window.location=event.srcElement.url;
}

/************************************************************/

/*
 * ieMenuIsV set to zero, by new msdb()
 * since we get both onclick events at once
 * the first being the oone from the <A ...>
 * we then set it to 2, so then it doesn't
 * get immediately hidden by the next one
 * the the first document.onclick set it back to
 * 1, so the next one turns it off
 */

function
msdbShowIeMenu(menuid, arg1, arg2)
{
	ALERT1("msdbShowIeMenu");
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
	/*	document.body.onclick=msdbHideIeMenu;	*/
	document.onclick=msdbHideIeMenu;
}

/************************************************************/

function
msdbHideIeMenu()
{
	ALERT1("msdbHideIeMenu");
	if ( ht.ieMenuIsV == 2 ) {
		ht.ieMenuIsV = 1;
		return;
	}
	if ( ht.ieMenuIsV == 0 )
		return;
	ht.ieMenuIsV = 0;
	hidemenuie5(ht.menuid);
}

/************************************************************/
