function setFocus()
{
	document.forms.focusform.elements[ 0 ].focus();
}

function xfVendorsAll()
{
	for ( var i = 0; i < document.indexcontrol.elements.length; i++ )
	{
		var e = document.indexcontrol.elements[ i ];
		if ( e.name.substr( 0, 10 ) == 'xf_vendor_' )
			e.checked = true;
	}
}

function xfVendorsNone()
{
	for ( var i = 0; i < document.indexcontrol.elements.length; i++ )
	{
		var e = document.indexcontrol.elements[ i ];
		if ( e.name.substr( 0, 10 ) == 'xf_vendor_' )
			e.checked = false;
	}
}

function xfArchsAll()
{
	for ( var i = 0; i < document.indexcontrol.elements.length; i++ )
	{
		var e = document.indexcontrol.elements[ i ];
		if ( e.name.substr( 0, 8 ) == 'xf_arch_' )
			e.checked = true;
	}
}

function xfArchsNone()
{
	for ( var i = 0; i < document.indexcontrol.elements.length; i++ )
	{
		var e = document.indexcontrol.elements[ i ];
		if ( e.name.substr( 0, 8 ) == 'xf_arch_' )
			e.checked = false;
	}
}
