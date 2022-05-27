# TYPO3 extension patch10011

## What is does

This extension provides methods to enhance the TypoScript with a userFunc and a check for installed extensions and their version numbers. TYPO3 from version 9.5 to 11.5 is supported.

Use the forum at https://www.jambage.com to ask questions and find answers.
The documentation file `manual.odt` is available in the doc folder.

## Example
```
[ext("tt_news", "version") > "03.05.99"]
  Typoscript lines which will be active only if tt_news in a version greater than 3.5.99 has been installed.
[end]
```

## Enhancement

Use the hook $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['userFunc'] in order to enhance this extension by any other TYPO3 extension which wants to provide its special userFunc to admins.

The old hook $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['patch10011']['includeLibs'] has been removed.
