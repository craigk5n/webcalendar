/* $Id$  */

function selAdd(btn){
  var grplist = document.editgroup.group;
  var grplength = grplist.length;
  var isUnique = true;
  var i,j;
   with (document.editgroup.users)
   {
     for (i = 0; i < length; i++) {
       if(options[i].selected) {
         with (options[i]) {                              
           for ( j=0;j<grplength;j++ ) {
             if (grplist.options[j].value == value ) {
               isUnique = false;
               j=grplength;
             }
           }
           if ( isUnique) {
             grplist.options[grplength]  = new Option( text, value ); 
						 grplength++;
					 }
           options[i].selected = false;
					 isUnique = true;
         } //end with options
       }
     } // end for loop
   } // end with document
}

function selRemove(btn){
   with (document.editgroup.group)
   { 
		 for (i = length -1; i >= 0; i--)
		 {
			 if(options[i].selected){
			   options[i] = null;
		   } 
		 } // end for loop
   } // end with document
}

function selAll(btn){
   with (document.editgroup.group)
   { 
		 for (i = length -1; i >= 0; i--)
		 {
			 options[i].selected = true;
		 } // end for loop
   } // end with document
}


