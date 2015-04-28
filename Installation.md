
# User setup

This guide is for setting up a working KB CMS setup with just the binaries needed to use it.

TODO: Create this guide.

See [the user guide](https://github.com/kbitdk/kbcms/blob/master/UserGuide.md) for how to use it once installed.

# Development setup

This guide is for setting up a working KB CMS setup with the source code and anything necessary to contribute to or modify KB CMS.
It's made for Ubuntu/Debian, but it should work on other systems. Perhaps with some tweaking.

If you already use Go, you can just "go get github.com/kbitdk/kbcms". If you don't, run these commands:

```
# Install required packages
sudo apt-get -y install golang git
# Set up a Go environment
mkdir $HOME/go
export GOPATH=$HOME/go
export PATH=$PATH:$GOPATH/bin
echo "# Env vars for Go" >> ~/.bashrc
echo "export GOPATH=\$HOME/go" >> ~/.bashrc
echo "export PATH=\$PATH:\$GOPATH/bin" >> ~/.bashrc
# Get the project
go get github.com/kbitdk/kbcms
```

See [the user guide](https://github.com/kbitdk/kbcms/blob/master/UserGuide.md) for how to use it once installed.

