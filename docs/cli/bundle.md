`bundle`
--------

Bundles the PHP code along with the runtime

### Usage

* `bundle [-o|--output [OUTPUT]] [-rt|--runtime [RUNTIME]] [-t|--type [TYPE]] [--] <code>`

Bundles the PHP code along with the runtime

### Arguments

#### `code`

Path to PHP code

* Is required: yes
* Is array: no
* Default: `NULL`

### Options

#### `--output|-o`

The output file

* Accept value: yes
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `'bundle.wasm'`

#### `--runtime|-rt`

Path to runtime or runtime version

* Accept value: yes
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `'latest'`

#### `--type|-t`

Code type

* Accept value: yes
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `'script'`

#### `--help|-h`

Display help for the given command. When no command is given display help for the list command

* Accept value: no
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `false`

#### `--ansi|--no-ansi`

Force (or disable --no-ansi) ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Is negatable: yes
* Default: `NULL`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Is negatable: no
* Default: `false`