
package main

/* KBTB.go
The TB in KBTB is short for toolbox and it's a file that gathers functions that
could potentially be separated to its own package for other programs to use.
*/

import (
	"fmt"
	"os"
)

// Throw exception on error
func errHandler(err error) {
	if err != nil { panic(err) }
}

// Print variable info (similar to var_dump() and Data::Dumper() in other languages)
func dump(v interface{}) {
	var t interface{}
	t = v
	if fmt.Sprintf("%T",v) == "*os.File" { v = v.(*os.File).Name() }
	fmt.Printf("%T: %#v\n", t, v)
}

