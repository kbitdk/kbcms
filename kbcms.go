package main

import (
	"fmt"
	"os"
	"path"
	"encoding/json"
	"io/ioutil"
	"html/template"
	"log"
	"path/filepath"
)

// The settings.json file format
type Cfg struct {
	OutputDir string
}


func usage(s string) { // Explain usage - apologies for the formatting with the multiline strings
	switch s {
	case "":
		fmt.Println(`KB CMS is a Go-based CMS aimed at easy setup and maintenance without sacrificing functionality.

Usage:

	kbcms command [arguments]

The commands are:

    build       Compile site from source files

Use "kbcms help [command]" for more information about a command.
`)
	case "build":
		fmt.Println(`Usage: kbcms build [source dir]

Build compiles a website from source files in the given directory.

If no source directory is given, the current working directory is used.

Warning! This will output the compiled site to the folder specified in the
         settings.json file and overwrite any conflicting files there.
`)
	default: panic("Invalid input.")
	}
}

func main() {
	argsLen := len(os.Args)
	if argsLen < 2 { // No args, no go
		usage("")
		return
	}

	switch os.Args[1] {
	case "help":
		switch argsLen {
			case 2: usage("")
			case 3: usage(os.Args[2])
			default: panic("Invalid input.")
		}
	case "build":
		// Get source dir
		var srcdir string
		switch argsLen {
			case 2: // Use current working dir if none given
				var err error
				srcdir, err = os.Getwd()
				errHandler(err)
			case 3:
				srcdir = os.Args[2]
			default: panic("Invalid input.")
		}
		srcdir = path.Clean(srcdir)

		// Read cfg
		cfgFile, err := ioutil.ReadFile(srcdir+"/settings.json")
		if os.IsNotExist(err) {
			log.Fatalln("Invalid source dir (no settings.json file).")
		} else { errHandler(err) }
		var cfg Cfg
		errHandler(json.Unmarshal(cfgFile, &cfg))

		// Read template and create a temp dir for building the project
		t, err := template.ParseFiles(srcdir+"/templates/design.html") // TODO: Support multiple templates
		errHandler(err)
		tmpdir, err := ioutil.TempDir("", "kbcms_") // TODO: Look into deleting this folder in case of unhandled errors
		errHandler(err)

		// Iterate pages
		pages, err := filepath.Glob(srcdir+"/content/*.html") // TODO: Support sub-folders
		for _, page := range pages {
			// Read the content
			pageContent, err := ioutil.ReadFile(page)
			errHandler(err)

			// Writer for the file
			output, err := os.Create(tmpdir+"/"+path.Base(page))
			errHandler(err)
			defer output.Close()

			// Apply the content to the template
			errHandler(t.Execute(output, map[string]template.HTML{"Content":template.HTML(pageContent)}))
		}

		// Copy files from srcdir+"/files/*"
		extraFiles, err := filepath.Glob(srcdir+"/files/*")
		errHandler(err)
		for _, extraFile := range extraFiles {
			errHandler(copyFile(extraFile, tmpdir+"/"+path.Base(extraFile)))
		}

		// Publish project and remove temp folder
		tmpFiles, err := filepath.Glob(tmpdir+"/*")
		errHandler(err)
		for _, tmpFile := range tmpFiles {
			errHandler(os.Rename(tmpFile, path.Clean(cfg.OutputDir)+"/"+path.Base(tmpFile)))
		}

		errHandler(os.RemoveAll(tmpdir))
	default:
		fmt.Println("Invalid input.")
	}
}



