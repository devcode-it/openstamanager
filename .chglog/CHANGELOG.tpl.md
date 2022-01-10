#  {{ .Info.Title }}

{{ if .Versions -}}
{{ if .Unreleased.CommitGroups -}}
<a name="unreleased"></a>

## Unreleased

{{ range .Unreleased.CommitGroups -}}

###  {{ .Title }}

{{ range .Commits -}}

- [`{{ .Hash.Short }}`]({{ $.Info.RepositoryURL }}/commit/{{ .Hash.Long }}) {{ if .Scope }}**{{ .Scope }}:** {{ end }}{{ .Subject }}
  {{ if .Body }}
  {{ indent .Body 4 }}
  {{ end -}}
  {{ end }}
  {{ end -}}
  {{ end -}}
  {{ end -}}

{{ range .Versions }}
<a name="{{ .Tag.Name }}"></a>

##  {{ if .Tag.Previous }}[ {{ .Tag.Name }}]( {{ $.Info.RepositoryURL }}/compare/ {{ .Tag.Previous.Name }}... {{ .Tag.Name }}) {{ else }} {{ .Tag.Name }} {{ end }}

> Released on {{ datetime "January 02, 2006" .Tag.Date }}

{{ range .CommitGroups -}}

###  {{ .Title }}

{{ range .Commits -}}

- [`{{ .Hash.Short }}`]({{ $.Info.RepositoryURL }}/commit/{{ .Hash.Long }}) {{ if .Scope }}**{{ .Scope }}:** {{ end }}{{ .Subject }}
  {{ if .Body }}
  {{ indent .Body 4 }}
  {{ end -}}
  {{ end }}
  {{ end -}}

{{- if .RevertCommits -}}

### ⏪ Reverts

{{ range .RevertCommits -}}

- [`{{ .Hash.Short }}`]({{ $.Info.RepositoryURL }}/commit/{{ .Hash.Long }}) {{ .Revert.Header }}
  {{ if .Body }}
  {{ indent .Body 4 }}
  {{ end -}}
  {{ end }}
  {{ end -}}

{{- if .MergeCommits -}}

### 🔀 Pull Requests

{{ range .MergeCommits -}}

- [`{{ .Hash.Short }}`]({{ $.Info.RepositoryURL }}/commit/{{ .Hash.Long }}) {{ .Header }}
  {{ if .Body }}
  {{ indent .Body 4 }}
  {{ end -}}
  {{ end }}
  {{ end -}}

{{- if .NoteGroups -}}
{{ range .NoteGroups -}}

###  {{ .Title }}

{{ range .Notes }}


{{ .Body }}
{{ end }}
{{ end -}}
{{ end -}}
{{ end -}}
