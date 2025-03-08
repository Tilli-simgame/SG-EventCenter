document.addEventListener("DOMContentLoaded", () => {
  // Set current year in footer
  document.getElementById("currentYear").textContent = new Date().getFullYear()

  // Mobile menu toggle
  const mobileMenuBtn = document.getElementById("mobileMenuBtn")
  const mobileMenu = document.getElementById("mobileMenu")

  mobileMenuBtn.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden")
  })

  // Tab functionality
  const tabButtons = document.querySelectorAll("[data-tab]")
  const tabPanels = document.querySelectorAll(".tab-panel")

  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const targetTab = this.dataset.tab

      // Update active tab button
      tabButtons.forEach((btn) => {
        if (btn.dataset.tab === targetTab) {
          btn.classList.add("border-primary", "text-primary")
          btn.classList.remove(
            "border-transparent",
            "text-primary-light",
            "hover:border-primary-light",
            "hover:text-primary",
          )
        } else {
          btn.classList.remove("border-primary", "text-primary")
          btn.classList.add(
            "border-transparent",
            "text-primary-light",
            "hover:border-primary-light",
            "hover:text-primary",
          )
        }
      })

      // Show active tab panel
      tabPanels.forEach((panel) => {
        if (panel.id === targetTab) {
          panel.classList.remove("hidden")
        } else {
          panel.classList.add("hidden")
        }
      })
    })
  })

  // Function to determine the data directory path
  function getDataDirectoryPath() {
    // In a real environment, we would check if /events directory exists
    // Since we can't directly check directories in client-side JS, we'll use a simple approach

    // Check if we're in production by looking for a URL parameter or hostname
    const isProduction =
      window.location.hostname !== "localhost" &&
      !window.location.hostname.includes("127.0.0.1") &&
      !window.location.hostname.includes(".local")

    // Log the environment and path for debugging
    console.log(`Environment: ${isProduction ? "Production" : "Development"}`)
    const path = isProduction ? "/events/" : "data/"
    console.log(`Using data path: ${path}`)

    return path
  }

  // Function to get all event files from the server
  async function getEventFiles() {
    try {
      console.log("Fetching event files list from PHP script...")
      // Call the PHP script that lists all JSON files in the data directory
      const response = await fetch("get-event-files.php")

      if (!response.ok) {
        console.error("Failed to fetch event files list:", response.status, response.statusText)
        // Fallback to known files if the PHP script fails
        return ["este250316.json", "koulu250309.json"]
      }

      const data = await response.json()
      console.log("PHP script response:", data)
      return data.files || []
    } catch (error) {
      console.error("Error fetching event files list:", error)
      // Fallback to known files if there's an error
      return ["este250316.json", "koulu250309.json"]
    }
  }

  // Function to fetch event data from JSON files
  async function fetchEventData() {
    try {
      // Get list of JSON files from the PHP script
      const eventFiles = await getEventFiles()

      if (eventFiles.length === 0) {
        console.warn("No event files found in the data folder")
        return []
      }

      console.log("Found event files:", eventFiles)

      const events = []
      const dataPath = getDataDirectoryPath()

      // Fetch each event file
      for (const file of eventFiles) {
        try {
          const fileUrl = `${dataPath}${file}`
          console.log(`Attempting to fetch file: ${fileUrl}`)

          const response = await fetch(fileUrl)

          if (!response.ok) {
            console.error(`Failed to fetch ${file}: ${response.status} ${response.statusText}`)
            continue
          }

          console.log(`Successfully fetched ${file}, parsing JSON...`)
          const eventData = await response.json()
          console.log(`JSON parsed successfully for ${file}`)

          // Skip files that don't have the expected structure
          if (!eventData.classList || !Array.isArray(eventData.classList)) {
            console.warn(`File ${file} doesn't have the expected event structure`)
            continue
          }

          // Map the JSON data to our event format
          const eventType = determineEventType(file)

          // Format date for display
          const displayDate = formatDateRange(eventData.date, eventData.lastDate)

          // Get a summary of classes
          const classCount = eventData.classList.length
          const firstClasses = eventData.classList.slice(0, 3).join(", ")
          const classSummary = classCount > 3 ? `${firstClasses} ja ${classCount - 3} muuta luokkaa` : firstClasses

          // Create an event object for the entire event file
          events.push({
            id: file.replace(".json", ""),
            title: `${getEventTitle(file)} - ${eventData.location}`,
            date: displayDate,
            time: "8:00 - 18:00", // Default time if not specified in JSON
            location: eventData.location,
            type: eventType,
            image: `https://placehold.co/600x400?text=${encodeURIComponent(getEventTitle(file))}`,
            registrationOpen: isRegistrationOpen(eventData.date),
            organizer: eventData.organizer,
            organizerEmail: eventData.organizerEmail,
            eventType: eventData.type,
            classSummary: classSummary,
            classCount: classCount,
            originalData: eventData,
            fileName: file,
          })

          console.log(`Successfully processed ${file}`)
        } catch (error) {
          console.error(`Error processing ${file}:`, error)
        }
      }

      console.log(`Total events processed: ${events.length}`)

      // Sort events by date (closest first)
      events.sort((a, b) => {
        const dateA = new Date(a.originalData.date)
        const dateB = new Date(b.originalData.date)
        return dateA - dateB
      })

      return events
    } catch (error) {
      console.error("Error fetching event data:", error)
      return []
    }
  }

  // Helper function to get a title for the event based on the file name
  function getEventTitle(fileName) {
    // Determine event title based only on filename
    if (fileName.includes("este")) {
      return "Esteratsastuskilpailu"
    } else if (fileName.includes("koulu")) {
      return "Kouluratsastuskilpailu"
    } else if (fileName.includes("kentta")) {
      return "Kenttäkilpailu"
    } else if (fileName.includes("nayttely")) {
      return "Hevosnäyttely"
    } else if (fileName.includes("kurssi") || fileName.includes("clinic")) {
      return "Kurssi"
    }

    // Default title if we can't determine
    return "Tapahtuma"
  }

  // Helper function to determine event type based on filename
  function determineEventType(fileName) {
    // Determine event type based only on filename
    if (fileName.includes("este") || fileName.includes("koulu") || fileName.includes("kentta")) {
      return "competition" // Competitions (jumping, dressage, eventing)
    } else if (fileName.includes("nayttely")) {
      return "show" // Horse show
    } else if (fileName.includes("kurssi")) {
      return "clinic" // Training clinic
    }

    // Default to competition if we can't determine
    return "competition"
  }

  // Helper function to format date range
  function formatDateRange(startDate, endDate) {
    if (!startDate) return "TBA"

    // Parse dates
    const start = new Date(startDate)
    const end = endDate ? new Date(endDate) : null

    // Format options
    const options = { day: "numeric", month: "long", year: "numeric" }

    if (!end || start.getTime() === end.getTime()) {
      // Single day event
      return start.toLocaleDateString("fi-FI", options)
    } else {
      // Multi-day event
      return `${start.toLocaleDateString("fi-FI", { day: "numeric", month: "long" })} - ${end.toLocaleDateString("fi-FI", options)}`
    }
  }

  // Helper function to check if registration is still open
  function isRegistrationOpen(eventDate) {
    if (!eventDate) return false

    const now = new Date()
    const event = new Date(eventDate)

    // Registration closes 3 days before the event (arbitrary rule for this example)
    const registrationCloseDate = new Date(event)
    registrationCloseDate.setDate(event.getDate() - 3)

    return now < registrationCloseDate
  }

  // Function to create event card HTML
  function createEventCard(event) {
    // Determine badge class based on event type
    let badgeClass = ""
    let typeText = ""
    switch (event.type) {
      case "show":
        badgeClass = "bg-blue-100 text-blue-800"
        typeText = "Näyttely"
        break
      case "clinic":
        badgeClass = "bg-green-100 text-green-800"
        typeText = "Kurssi"
        break
      case "competition":
        badgeClass = "bg-red-100 text-red-800"
        typeText = "Kilpailu"
        break
      case "eventing":
        badgeClass = "bg-purple-100 text-purple-800"
        typeText = "Kenttäkilpailu"
        break
      default:
        badgeClass = "bg-gray-100 text-gray-800"
        typeText = event.type
    }

    // Create event page URL
    const eventPageUrl = `event.html?event=${event.id}`

    // Create the card HTML
    return `
      <div class="event-card" data-type="${event.type}" data-location="${event.location.toLowerCase().replace(/\s+/g, "-")}">
        <div class="bg-white rounded-lg overflow-hidden border border-secondary hover:shadow-md transition-shadow h-full">
          <div class="relative">
            <div class="h-48 overflow-hidden">
              <img src="${event.image}" class="w-full h-full object-cover" alt="${event.title}">
            </div>
            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-medium ${badgeClass}">
              ${typeText}
            </span>
          </div>
          <div class="p-4">
            <h3 class="text-xl font-semibold text-primary mb-2">${event.title}</h3>
            <div class="space-y-2 text-primary-light">
              <div class="flex items-center gap-2">
                <i class="fas fa-calendar text-sm"></i>
                <span>${event.date}</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-clock text-sm"></i>
                <span>${event.time}</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-map-marker-alt text-sm"></i>
                <span>${event.location}</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-user text-sm"></i>
                <span>${event.organizer || "Ei järjestäjätietoa"}</span>
              </div>
              <div class="flex items-center gap-2">
                <i class="fas fa-list text-sm"></i>
                <span>${event.classSummary}</span>
              </div>
            </div>
          </div>
          <div class="p-4 pt-0 flex justify-between items-center">
            <a href="${eventPageUrl}" class="${event.registrationOpen ? "bg-primary hover:bg-primary-light text-white" : "border border-primary-light text-primary"} px-4 py-2 rounded-md text-center" 
                    ${!event.registrationOpen ? 'aria-disabled="true"' : ""}>
              ${event.registrationOpen ? "Ilmoittaudu nyt" : "Ilmoittautuminen suljettu"}
            </a>
            <a href="${eventPageUrl}" class="text-primary hover:text-primary-light">
              Lisätiedot
            </a>
          </div>
        </div>
      </div>
    `
  }

  // Function to populate event containers
  async function populateEvents() {
    const allEventsContainer = document.getElementById("allEventsContainer")
    const showsContainer = document.getElementById("showsContainer")
    const clinicsContainer = document.getElementById("clinicsContainer")
    const competitionsContainer = document.getElementById("competitionsContainer")

    // Clear containers
    allEventsContainer.innerHTML = '<div class="col-span-full text-center py-8">Ladataan tapahtumia...</div>'
    showsContainer.innerHTML = ""
    clinicsContainer.innerHTML = ""
    competitionsContainer.innerHTML = ""

    try {
      console.log("Starting to fetch event data...")
      // Fetch events from JSON files
      const events = await fetchEventData()
      console.log(`Fetched ${events.length} events`)

      if (events.length === 0) {
        allEventsContainer.innerHTML = '<div class="col-span-full text-center py-8">Ei tapahtumia saatavilla.</div>'
        return
      }

      // Clear loading message
      allEventsContainer.innerHTML = ""

      // Populate containers
      events.forEach((event) => {
        const eventCard = createEventCard(event)

        // Add to all events
        allEventsContainer.innerHTML += eventCard

        // Add to specific category container
        if (event.type === "show") {
          showsContainer.innerHTML += eventCard
        } else if (event.type === "clinic") {
          clinicsContainer.innerHTML += eventCard
        } else if (event.type === "competition" || event.type === "eventing") {
          competitionsContainer.innerHTML += eventCard
        }
      })

      // Check if category containers are empty
      if (showsContainer.innerHTML === "") {
        showsContainer.innerHTML = '<div class="col-span-full text-center py-8">Ei näyttelyitä saatavilla.</div>'
      }
      if (clinicsContainer.innerHTML === "") {
        clinicsContainer.innerHTML = '<div class="col-span-full text-center py-8">Ei kursseja saatavilla.</div>'
      }
      if (competitionsContainer.innerHTML === "") {
        competitionsContainer.innerHTML = '<div class="col-span-full text-center py-8">Ei kilpailuja saatavilla.</div>'
      }
    } catch (error) {
      console.error("Error populating events:", error)
      allEventsContainer.innerHTML =
        '<div class="col-span-full text-center py-8">Virhe ladattaessa tapahtumia. Yritä myöhemmin uudelleen.</div>'
    }
  }

  // Initialize event display
  populateEvents()

  // Filter events based on selected filters
  function filterEvents() {
    const typeFilter = document.getElementById("eventTypeFilter").value
    const dateFilter = document.getElementById("dateFilter").value

    const eventCards = document.querySelectorAll(".event-card")
    let visibleCount = 0

    eventCards.forEach((card) => {
      let showCard = true

      // Filter by type
      if (typeFilter !== "all" && card.dataset.type !== typeFilter) {
        showCard = false
      }

      // Date filtering would require more complex logic with actual dates
      // This is a simplified version
      if (dateFilter !== "all") {
        // For demo purposes, we're not implementing full date filtering
        // In a real application, you would compare actual dates
      }

      // Show or hide the card
      card.style.display = showCard ? "" : "none"
      if (showCard) visibleCount++
    })

    // Show message if no events match the filters
    const activeTabId = document.querySelector("[data-tab].border-primary").dataset.tab
    const activeContainer = document.getElementById(activeTabId)
    const noResultsMsg = activeContainer.querySelector(".no-results-message")

    if (visibleCount === 0) {
      if (!noResultsMsg) {
        const message = document.createElement("div")
        message.className = "no-results-message col-span-full text-center py-8"
        message.textContent = "Ei hakuehtoja vastaavia tapahtumia."
        activeContainer.appendChild(message)
      }
    } else if (noResultsMsg) {
      noResultsMsg.remove()
    }
  }

  // Add event listeners to filters
  document.getElementById("eventTypeFilter").addEventListener("change", filterEvents)
  document.getElementById("dateFilter").addEventListener("change", filterEvents)

  // Load more events button (demo functionality)
  document.getElementById("loadMoreBtn").addEventListener("click", () => {
    alert("Oikeassa sovelluksessa tämä lataisi lisää tapahtumia palvelimelta.")
  })
})

