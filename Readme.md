# TYPO3 Extension `rss_fluid`

This extension renders a RSS feed with fluid. Thanks to [simplepie](https://github.com/simplepie/simplepie) for the code!

**Features**

- Download embedded media files and render them from local
- No database records

## Usage

- Download the extension
- Include the TypoScript by selecting it in the *Include Static* section or with `<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rss_fluid/Configuration/TypoScript/setup.typoscript">`
- Create a new content element "RSS Fluid", provide the URL and done

## Configuration

The only configuration is currently the location where files are persisted. **This directory must exist**.

```
tt_content.rss_fluid.20.import_path = 1:/fileadmin/rss_import/
```

See the TypoScript as reference
