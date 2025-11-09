package com.sharemycard.android.util

data class CardTheme(
    val id: String,
    val name: String,
    val primaryColor: String,
    val secondaryColor: String
)

object CardThemes {
    val themes = listOf(
        CardTheme("professional-blue", "Professional Blue", "#667eea", "#764ba2"),
        CardTheme("minimalist-gray", "Minimalist Gray", "#2d3748", "#4a5568"),
        CardTheme("creative-sunset", "Creative Sunset", "#f093fb", "#f5576c"),
        CardTheme("corporate-green", "Corporate Green", "#11998e", "#38ef7d"),
        CardTheme("tech-purple", "Tech Purple", "#4776e6", "#8e54e9"),
        CardTheme("modern-red", "Modern Red", "#e53935", "#d32f2f"),
        CardTheme("ocean-blue", "Ocean Blue", "#0277bd", "#01579b"),
        CardTheme("royal-gold", "Royal Gold", "#ffa726", "#f57c00"),
        CardTheme("forest-green", "Forest Green", "#43a047", "#2e7d32"),
        CardTheme("slate-black", "Slate Black", "#37474f", "#263238"),
        CardTheme("coral-pink", "Coral Pink", "#ff7043", "#f4511e"),
        CardTheme("electric-teal", "Electric Teal", "#00acc1", "#00838f")
    )
    
    fun getThemeById(id: String?): CardTheme? {
        return themes.find { it.id == id }
    }
    
    val defaultTheme = themes.first()
}

