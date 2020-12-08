# LC-ScriptEngine Tools
This is my toolset for LC-ScriptEngine visual novel engine.

Tested on `LC-ScriptEngine ver.1.600` (kuroinu_1).

## lcse_pack.php
This is unpacking/packing script for LCSE packages like lcsebody1, lcsebody2, ... which comes with a huge binary and a `.lst` file.

Inspired by [LCSELocalizationTools](https://github.com/cqjjjzr/LCSELocalizationTools), his tool comes with a few limitations and is not very easy to use, therefore I made this simple script.

Usage:
```bash
lcse_pack.php <u[npack]|p[ack]> <Dir|File> [Output]
```

Unpacking example:

```bash
# lcsebody1 + lcsebody1.lst -> out/<tons of files>
lcse_pack.php u lcsebody1 out
```

Packing example:
```bash
# out/<tons of files> -> lcsebody1 + lcsebody1.lst
lcse_pack.php p out lcsebody1
```
