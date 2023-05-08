#!/usr/bin/env node

import fs from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import picocolors from 'picocolors'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(fileURLToPath(import.meta.url))

const iconsDir = path.join(__dirname, '../icons/')
const pagesDir = path.join(__dirname, '../docs/content/icons/')

const VERBOSE = process.argv.includes('--verbose')

function capitalizeFirstLetter(string) {
  return (string.charAt(0).toUpperCase() + string.slice(1)).split('-').join(' ')
}

async function main(file) {
  const iconBasename = path.basename(file, path.extname(file))
  const iconTitle = capitalizeFirstLetter(iconBasename)
  const pageName = path.join(pagesDir, `${iconBasename}.md`)

  const pageTemplate = `---
title: ${iconTitle}
categories:
tags:
---
`

  try {
    await fs.access(pageName, fs.F_OK)

    if (VERBOSE) {
      console.log(`${picocolors.cyan(iconBasename)}: Page already exists; skipping`)
    }
  } catch {
    await fs.writeFile(pageName, pageTemplate)
    console.log(picocolors.green(`${iconBasename}: Page created`))
  }
}

(async () => {
  try {
    const basename = path.basename(__filename)
    const timeLabel = picocolors.cyan(`[${basename}] finished`)

    console.log(picocolors.cyan(`[${basename}] started`))
    console.time(timeLabel)

    const files = await fs.readdir(iconsDir)

    await Promise.all(files.map(file => main(file)))

    const filesLength = files.length

    console.log(picocolors.green('\nSuccess, %s page%s prepared!'), filesLength, filesLength === 1 ? '' : 's')
    console.timeEnd(timeLabel)
  } catch (error) {
    console.error(error)
    process.exit(1)
  }
})()
