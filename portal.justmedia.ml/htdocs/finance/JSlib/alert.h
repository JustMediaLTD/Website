/* $Id: alert.h,v 1.2 2004/07/28 08:10:09 engine Exp engine $  */
/* Copyright (c) Ohad Aloni 1990-2004. All rights reserved. */
/* Released under http://www.engine.com/license.txt (BSD) */
/************************************************************/
/*
 * environment controlled alert level system for debugging
 *
 */
/************************************************************/
#define ALERT(m, level) alert(__FILE__ + ": " + __LINE__ + ": " + level + ": " + m);
/************************************************************/

/*
 * alert level 0 always stays by virtue of jsal 0
 * which is normal operation.
 *
 * alert level 1 is function tracing
 * alert level 2 is everything interactive. (all functions that are called for only
 *    as a result of user request are traced).
 * alert level 10 is function tracing of heisenberg functions like msdbSaveMouse
 * other alert reasons are not yet conventionalized
 */

#ifdef ALERT_LEVEL_0
#define ALERT0(m) ALERT(m, 0)
#else
#define ALERT0(m)
#endif

#ifdef ALERT_LEVEL_1
#define ALERT1(m) ALERT(m, 1)
#else
#define ALERT1(m)
#endif

#ifdef ALERT_LEVEL_2
#define ALERT2(m) ALERT(m, 2)
#else
#define ALERT2(m)
#endif

#ifdef ALERT_LEVEL_3
#define ALERT3(m) ALERT(m, 3)
#else
#define ALERT3(m)
#endif

#ifdef ALERT_LEVEL_4
#define ALERT4(m) ALERT(m, 4)
#else
#define ALERT4(m)
#endif

#ifdef ALERT_LEVEL_10
#define ALERT10(m) ALERT(m, 10)
#else
#define ALERT10(m)
#endif

/************************************************************/
