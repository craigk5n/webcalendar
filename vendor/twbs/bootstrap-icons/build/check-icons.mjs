#!/usr/bin/env node

import fs from 'node:fs/promises'
import path from 'node:path'
import process from 'node:process'
import { fileURLToPath } from 'node:url'
import picocolors from 'picocolors'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(fileURLToPath(import.meta.url))

const fontJsonPath = path.join(__dirname, '../font/bootstrap-icons.json')
const iconsDir = path.join(__dirname, '../icons/')

;(async () => {
  try {
    const basename = path.basename(__filename)
    const timeLabel = picocolors.cyan(`[${basename}] finished`)

    console.log(picocolors.cyan(`[${basename}] started`))
    console.time(timeLabel)

    const fontJsonString = await fs.readFile(fontJsonPath, 'utf8')
    const fontJson = JSON.parse(fontJsonString)
    const svgFiles = await fs.readdir(iconsDir)

    const jsonIconList = Object.keys(fontJson)
    const svgIconList = svgFiles.map(svg => path.basename(svg, '.svg'))

    const onlyInJson = jsonIconList.filter(icon => !svgIconList.includes(icon))
    const onlyInSvg = svgIconList.filter(icon => !jsonIconList.includes(icon))

    if (onlyInJson.length === 0 || onlyInSvg === 0) {
      console.log(picocolors.green('Success, found no differences!'))
      console.timeEnd(timeLabel)

      return
    }

    if (onlyInJson.length > 0) {
      console.error(picocolors.red(`Found additional icons in ${fontJsonPath}:`))

      for (const icon of onlyInJson) {
        console.log(`  - ${picocolors.red(icon)}`)
      }
    }

    if (onlyInSvg.length > 0) {
      console.error(picocolors.red('Found additional icons in SVG files:'))

      for (const icon of onlyInSvg) {
        console.log(`  - ${picocolors.red(icon)}`)
      }
    }

    process.exit(1)
  } catch (error) {
    console.error(error)
    process.exit(1)
  }
})()
