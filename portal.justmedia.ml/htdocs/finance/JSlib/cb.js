function
theCB(cbTable, restrFname, restrVal)
{
        this.cbTable = cbTable ;
        this.restrFname = restrFname ;
        this.restrVal = restrVal ;
        this.data = null;
        return(this);
}



function
cbItem(category, date, amount, toFrom, notes, closed, entered, id)
{
        this.category = category ;
        this.date = date ;
        this.amount = amount ;
        this.toFrom = toFrom ;
        this.notes = notes ;
        this.closed = closed ;
        this.entered = entered ;
        this.id = id ;
        return(this);
}



function
cbRestrictView(which, val)
{
        f = new Array('category', 'toFrom', 'Statement');
        cbCmd('cbNoOp', 0, "&restrFname=" + f[which] + "&restrVal=" + escape(val));
}



function
cbNoOp()
{
        cbCmd('cbNoOp', 0, "");
}



function
cbNoCat()
{
        cbCmd('cbNoOp', 0, "");
}



function
cbForm(n)
{
        nnewForm = document.forms[0];
        changeForm = document.forms[1] ;


        if ( n == '' ) {
                if ( cb.isChange == true )
                        return(changeForm);
                else
                        return(nnewForm);
        }

        return(changeForm);
}



function
cbNewCopy()
{
        f = cbForm('Change');
        f.EA.value = "cbAdd" ;
        f.submit();
}



function
cbHideChange()
{
        cb.isChange = false ;
        cb.isNew = false ;

        msdbHideDialog('cbChangeId');
        msdbHideDialog('cbSELcategory');
        msdbHideDialog('cbSELtoFrom');
}



function
cbSetCat(val)
{
        cbForm('').category.value = val ;
        msdbHideDialog('cbSELcategory');
}



function
cbSetToF(val)
{
        cbForm('').toFrom.value = val ;
        msdbHideDialog('cbSELtoFrom');
}



function
cbSet(which, val)
{
        if ( which == 0 )
                cbSetCat(val);
        else
                cbSetToF(val);
}



function
cbToggleSelect(idname)
{
        ;
        vis = msdbDialogVisibility(idname);
        if ( vis == 1 ) {
                msdbHideDialog(idname);
                return;
        }

        msdbShowDialog(idname);

        msdbPlaceDialog(idname,
                        msdbTop.mouseX + document.body.scrollLeft - 8,
                        msdbTop.mouseY + document.body.scrollTop + 4
                        );
}



function
cbShowSelect(idname)
{
        cbToggleSelect(idname);
}



function
cbShowChange(id)
{
        d = cb.data;
        cb.isChange = true ;

        for(i=0;i<d.length;i++)
                if ( d[i].id == id )
                        break;
        if ( i == d.length ) {
                alert("Can not find data to change.");
                return;
        }
        g = d[i] ;
        if ( i == (d.length -1 ) )
                isLast = true;
        else
                isLast = false;

        f = cbForm('Change');

        f.category.value = g.category;
        f.date.value = g.date;
        f.amount.value = g.amount;
        f.toFrom.value = g.toFrom;
        if ( cb.isNew == true )
                f.notes.value = "" ;
        else
                f.notes.value = g.notes;
        f.closed.value = g.closed;
        f.entered.value = g.entered;
        f.id.value = g.id;


        msdbShowDialog('cbChangeId');
        msdbHideDialog('cbSELcategory');
        msdbHideDialog('cbSELtoFrom');


        msdbPlaceDialog('cbChangeId', 8, document.body.scrollTop + msdbTop.mouseY - 12);
        if ( cb.isNew ) {
                f.date.focus();
                f.date.select();
        } else {
                f.amount.focus();
                f.amount.select();
        }
}



function
cbFindItem(id)
{
        d = cb.data;

        for(i=0;i<d.length;i++)
                if ( d[i].id == id )
                        break;
        if ( i == d.length )
                return(msdb.undef);

        return(d[i]);
}



function
cbMonthly()
{
        cbCmd('cbMonthly', 0, '')
}



function
cbCmd(action, id, args)
{
        ;

        if ( action == 'cbCons' &&
                        cb.restrFname != '' &&
                        cb.restrFname != 'Statement'
                        ) {
                alert("Can not Consolidate when in item detail view");
                return;
        }

        if ( action == 'cbDelete' ) {
                g = cbFindItem(id) ;
                delMsg = "Are you Sure you want to Discard this entry?\n(" +
                        g.amount + ")" ;
                if ( ! confirm(delMsg) )
                        return;
        }

        if ( action == 'cbChange' ) {
                cbChangeId.className = 'cbChangeClass' ;
                f = cbForm('Change');
                f.EA.value = "cbUpdate" ;
                cbShowChange(id);
                return;
        }

        if ( action == 'cbNew' ) {
                cb.isNew = true ;
                cbChangeId.className = 'cbNewClass' ;
                f = cbForm('Change');
                f.EA.value = "cbAdd" ;
                cbShowChange(id);
                return;
        }

        if ( id != 0 )
                e = "&id=" + id ;
        else
                e = "" ;

        location = "?" +
                'cbTable=' + cb.cbTable +
                "&EA=" + action +
                e +
                "&t0=" + msdbRandom() +
                args
                ;
}
