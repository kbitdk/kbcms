
package main

/* KBTB.go
The TB in KBTB is short for toolbox and it's a file that gathers functions that
could potentially be separated to its own package for other programs to use.
*/

import (
	"fmt"
	"io"
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

func copyFileWTime(srcName, dstName string) (err error) {
	src, err := os.Open(srcName)
	errHandler(err)
	defer src.Close()

	dst, err := os.Create(dstName)
	errHandler(err)
	defer dst.Close()

	_, err = io.Copy(dst, src)
	errHandler(err)

	info, err := src.Stat()
	errHandler(err)

	err = os.Chtimes(dstName, info.ModTime(), info.ModTime())
	return err
}

